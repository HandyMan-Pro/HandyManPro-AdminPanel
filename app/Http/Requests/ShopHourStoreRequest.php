<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ShopHourStoreRequest extends FormRequest
{
    protected static $days = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'shop_hours' => ['required', 'array'],
            'shop_hours.*.day' => ['required', 'string', 'in:' . implode(',', self::$days)],
            'shop_hours.*.start_time' => ['required', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/'],
            'shop_hours.*.end_time' => ['required', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/'],
            'shop_hours.*.is_holiday' => ['sometimes', 'boolean'],
            'shop_hours.*.breaks' => ['sometimes', 'nullable', 'array'],
            'shop_hours.*.breaks.*.start_break' => ['required_with:shop_hours.*.breaks', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/'],
            'shop_hours.*.breaks.*.end_break' => ['required_with:shop_hours.*.breaks', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $shopHours = $this->input('shop_hours', []);
            foreach ($shopHours as $index => $row) {
                $day = $row['day'] ?? '';
                $start = $row['start_time'] ?? '';
                $end = $row['end_time'] ?? '';
                $isHoliday = !empty($row['is_holiday']);

                if (!$isHoliday && $start && $end) {
                    $startSec = strtotime($start);
                    $endSec = strtotime($end);
                    if ($endSec <= $startSec) {
                        $validator->errors()->add(
                            "shop_hours.{$index}.end_time",
                            __('messages.shop_hours_end_after_start', ['day' => ucfirst($day)])
                        );
                    }
                }

                $breaks = $row['breaks'] ?? [];
                if (is_array($breaks) && !$isHoliday && $start && $end) {
                    $dayStartNormalized = $this->normalizeTime($start);
                    $dayEndNormalized = $this->normalizeTime($end);
                    $dayStartSec = strtotime($dayStartNormalized);
                    $dayEndSec = strtotime($dayEndNormalized);

                    $breakTimes = [];
                    foreach ($breaks as $bIndex => $b) {
                        $s = $b['start_break'] ?? '';
                        $e = $b['end_break'] ?? '';

                        // Normalize time format for comparison
                        $sNormalized = $this->normalizeTime($s);
                        $eNormalized = $this->normalizeTime($e);

                        if ($s && $e) {
                            // Check if end time is after start time
                            if (strtotime($eNormalized) <= strtotime($sNormalized)) {
                                $validator->errors()->add(
                                    "shop_hours.{$index}.breaks.{$bIndex}.end_break",
                                    __('messages.shop_hours_break_end_after_start', ['day' => ucfirst($day)])
                                );
                            } else {
                                // Check break is within day working hours
                                $breakStartSec = strtotime($sNormalized);
                                $breakEndSec = strtotime($eNormalized);
                                if ($breakStartSec < $dayStartSec) {
                                    $validator->errors()->add(
                                        "shop_hours.{$index}.breaks.{$bIndex}.start_break",
                                        "Break start time must be on or after the day start time (" . $start . ") for " . ucfirst($day) . "."
                                    );
                                }
                                if ($breakEndSec > $dayEndSec) {
                                    $validator->errors()->add(
                                        "shop_hours.{$index}.breaks.{$bIndex}.end_break",
                                        "Break end time must be on or before the day end time (" . $end . ") for " . ucfirst($day) . "."
                                    );
                                }
                            }

                            // Check for duplicate break times (only if no within-day errors yet)
                            if (!$validator->errors()->has("shop_hours.{$index}.breaks.{$bIndex}.start_break") && !$validator->errors()->has("shop_hours.{$index}.breaks.{$bIndex}.end_break")) {
                                $breakKey = $sNormalized . '-' . $eNormalized;
                                if (isset($breakTimes[$breakKey])) {
                                    $validator->errors()->add(
                                        "shop_hours.{$index}.breaks.{$bIndex}.start_break",
                                        "Duplicate break time found for " . ucfirst($day) . ". Break time " . $s . " - " . $e . " already exists."
                                    );
                                } else {
                                    $breakTimes[$breakKey] = $bIndex;

                                    // Check for overlapping breaks (only check against already processed breaks)
                                    foreach ($breakTimes as $existingKey => $existingIndex) {
                                        if ($existingIndex !== $bIndex) {
                                            [$existingStart, $existingEnd] = explode('-', $existingKey);
                                            if ($this->breaksOverlap($sNormalized, $eNormalized, $existingStart, $existingEnd)) {
                                                $validator->errors()->add(
                                                    "shop_hours.{$index}.breaks.{$bIndex}.start_break",
                                                    "Overlapping break times found for " . ucfirst($day) . ". Break periods cannot overlap."
                                                );
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'shop_hours.*.day' => 'day',
            'shop_hours.*.start_time' => 'start time',
            'shop_hours.*.end_time' => 'end time',
            'shop_hours.*.breaks.*.start_break' => 'break start',
            'shop_hours.*.breaks.*.end_break' => 'break end',
        ];
    }

    /**
     * Normalize time format for comparison (ensure consistent format)
     */
    private function normalizeTime(string $time): string
    {
        // Remove AM/PM if present and convert to 24-hour format
        $time = trim($time);
        if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)/i', $time, $matches)) {
            $hour = (int)$matches[1];
            $minute = $matches[2];
            $ampm = strtoupper($matches[3]);

            if ($ampm === 'PM' && $hour !== 12) {
                $hour += 12;
            } elseif ($ampm === 'AM' && $hour === 12) {
                $hour = 0;
            }

            return sprintf('%02d:%02d:00', $hour, $minute);
        }

        // If already in 24-hour format, ensure seconds are present
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time)) {
            return $time . ':00';
        }

        return $time;
    }

    /**
     * Check if two break periods overlap
     */
    private function breaksOverlap(string $start1, string $end1, string $start2, string $end2): bool
    {
        $start1Time = strtotime($start1);
        $end1Time = strtotime($end1);
        $start2Time = strtotime($start2);
        $end2Time = strtotime($end2);

        // Check if breaks overlap (not just exact duplicates)
        return ($start1Time < $end2Time && $start2Time < $end1Time);
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        if (request()->is('api*')) {
            $data = [
                'status' => false,
                'message' => $validator->errors()->first(),
                'all_message' => $validator->errors()
            ];

            throw new HttpResponseException(response()->json($data, 422));
        }

        parent::failedValidation($validator);
    }
}
