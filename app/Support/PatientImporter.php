<?php

namespace App\Support;

use App\Models\Patient;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Throwable;

/**
 * Imports patients from the first sheet of an Excel (.xlsx) or CSV file.
 *
 * The first row must hold the headings; columns are matched by heading name,
 * in any order. The import is all-or-nothing: if any row is invalid, nothing is saved.
 */
class PatientImporter
{
    /**
     * Expected headings mapped to patient attributes.
     *
     * @var array<string, string>
     */
    public const COLUMNS = [
        'الاسم' => 'name',
        'رقم التليفون' => 'phone',
        'تاريخ الدخول' => 'admitted_on',
        'مشرف الوصول' => 'arrival_supervisor',
    ];

    /**
     * @return array{imported: int, errors: list<string>}
     */
    public static function import(string $path, string $extension): array
    {
        try {
            $rows = self::readRows($path, $extension);
        } catch (Throwable) {
            return ['imported' => 0, 'errors' => ['تعذر قراءة الملف. تأكد أنه ملف Excel (xlsx) أو CSV صحيح.']];
        }

        $headings = array_map(fn (mixed $heading): string => trim((string) $heading), array_shift($rows) ?? []);
        $columnIndexes = [];

        foreach (self::COLUMNS as $heading => $attribute) {
            $index = array_search($heading, $headings, true);

            if ($index === false) {
                return ['imported' => 0, 'errors' => ["العمود \"{$heading}\" غير موجود في الصف الأول من الملف."]];
            }

            $columnIndexes[$attribute] = $index;
        }

        $patients = [];
        $errors = [];

        foreach ($rows as $offset => $row) {
            $rowNumber = $offset + 2;

            $data = [];
            foreach ($columnIndexes as $attribute => $index) {
                $data[$attribute] = $row[$index] ?? null;
            }

            if (collect($data)->every(fn (mixed $value): bool => blank($value))) {
                continue;
            }

            $data = [
                'name' => trim((string) $data['name']),
                'phone' => self::normalizePhone($data['phone']),
                'admitted_on' => self::normalizeDate($data['admitted_on']),
                'arrival_supervisor' => trim((string) $data['arrival_supervisor']),
            ];

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'regex:/^01[0125]\d{8}$/'],
                'admitted_on' => ['required', 'date'],
                'arrival_supervisor' => ['required', 'string', 'max:255'],
            ], [
                'phone.regex' => 'رقم التليفون يجب أن يكون 11 رقمًا ويبدأ بـ 010 أو 011 أو 012 أو 015.',
                'admitted_on.required' => 'تاريخ الدخول فارغ أو بصيغة غير مفهومة (استخدم يوم/شهر/سنة).',
            ]);

            if ($validator->fails()) {
                $errors[] = "صف {$rowNumber}: ".implode(' ', $validator->errors()->all());

                continue;
            }

            $patients[] = $data;
        }

        if ($errors !== []) {
            return ['imported' => 0, 'errors' => $errors];
        }

        if ($patients === []) {
            return ['imported' => 0, 'errors' => ['الملف لا يحتوي على أي مرضى.']];
        }

        DB::transaction(function () use ($patients): void {
            foreach ($patients as $patient) {
                Patient::create($patient);
            }
        });

        return ['imported' => count($patients), 'errors' => []];
    }

    /**
     * @return list<list<mixed>>
     */
    private static function readRows(string $path, string $extension): array
    {
        /** @var ReaderInterface $reader */
        $reader = strtolower($extension) === 'csv' ? new CsvReader : new XlsxReader;
        $reader->open($path);

        $rows = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = $row->toArray();
                }

                break;
            }
        } finally {
            $reader->close();
        }

        return $rows;
    }

    /**
     * Excel often stores phone numbers as numbers and drops the leading zero.
     */
    private static function normalizePhone(mixed $phone): string
    {
        if (is_float($phone)) {
            $phone = number_format($phone, 0, '', '');
        }

        $digits = preg_replace('/\D/', '', strtr((string) $phone, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]));

        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    /**
     * Accepts real Excel dates, day/month/year text, or year-month-day text.
     */
    private static function normalizeDate(mixed $date): ?string
    {
        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        if (is_int($date) || is_float($date)) {
            return self::fromExcelSerial($date);
        }

        $date = trim((string) $date);

        if ($date === '') {
            return null;
        }

        foreach (['d/m/Y', 'j/n/Y', 'd-m-Y', 'j-n-Y', 'Y-m-d', 'Y/m/d'] as $format) {
            try {
                $parsed = Carbon::createFromFormat('!'.$format, $date);

                if ($parsed !== null && $parsed->format($format) === $date) {
                    return $parsed->format('Y-m-d');
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * Converts an Excel date serial number (days since 1899-12-30) when the cell isn't formatted as a date.
     */
    private static function fromExcelSerial(int|float $serial): ?string
    {
        if ($serial < 1 || $serial > 2958465) {
            return null;
        }

        return Carbon::create(1899, 12, 30)->addDays((int) floor($serial))->format('Y-m-d');
    }
}
