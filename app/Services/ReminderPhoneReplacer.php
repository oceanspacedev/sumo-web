<?php

namespace App\Services;

use App\Models\Insurance;
use App\Models\Rent;
use App\Models\WaNotificationSetting;
use Illuminate\Database\Eloquent\Builder;

class ReminderPhoneReplacer
{
    /**
     * Group every reminder number that is already stored on rent or insurance data.
     *
     * @return array<int, array{phone: string, rent_count: int, insurance_count: int, records: array<int, array{type: string, label: string}>}>
     */
    public function groups(): array
    {
        $groups = [];

        $this->collect($groups, Rent::query(), 'SEWA', function ($rent) {
            return $rent->rent_code.' — '.$rent->rented_detail;
        });

        $this->collect($groups, Insurance::query(), 'ASURANSI', function ($insurance) {
            return $insurance->policy_number.' — '.$insurance->insured_name;
        });

        ksort($groups);

        return array_values($groups);
    }

    /**
     * Replace only the selected number, leaving any other numbers on the same record.
     *
     * @return array{rent: int, insurance: int}
     */
    public function replace(string $oldPhone, string $newPhone): array
    {
        return [
            'rent' => $this->updateMatches(Rent::query(), $oldPhone, $newPhone),
            'insurance' => $this->updateMatches(Insurance::query(), $oldPhone, $newPhone),
        ];
    }

    /**
     * Remove the selected number. A record with no numbers left falls back to the global list.
     *
     * @return array{rent: int, insurance: int}
     */
    public function remove(string $oldPhone): array
    {
        return [
            'rent' => $this->updateMatches(Rent::query(), $oldPhone, null),
            'insurance' => $this->updateMatches(Insurance::query(), $oldPhone, null),
        ];
    }

    /**
     * @return string|null Updated list, or null when the old number is absent.
     */
    public function replaceInList(?string $raw, string $oldPhone, ?string $newPhone): ?string
    {
        $parts = array_map('trim', explode(',', (string) $raw));
        $changed = false;
        $result = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $digits = preg_replace('/[^0-9]/', '', $part);
            if ($digits === $oldPhone) {
                $changed = true;
                if ($newPhone !== null && $newPhone !== '') {
                    $result[] = $newPhone;
                }
            } else {
                $result[] = $part;
            }
        }

        if (! $changed) {
            return null;
        }

        return implode(', ', array_values(array_unique($result)));
    }

    protected function collect(array &$groups, Builder $query, string $type, callable $label): void
    {
        $this->eachRecord($query, function ($record) use (&$groups, $type, $label) {
            foreach (WaNotificationSetting::parsePhoneList($record->reminder_phones) as $phone) {
                if (! isset($groups[$phone])) {
                    $groups[$phone] = [
                        'phone' => $phone,
                        'rent_count' => 0,
                        'insurance_count' => 0,
                        'records' => [],
                    ];
                }

                if ($type === 'SEWA') {
                    $groups[$phone]['rent_count']++;
                } else {
                    $groups[$phone]['insurance_count']++;
                }

                $groups[$phone]['records'][] = [
                    'type' => $type,
                    'label' => $label($record),
                ];
            }
        });
    }

    protected function updateMatches(Builder $query, string $oldPhone, ?string $newPhone): int
    {
        $count = 0;

        $this->eachRecord($query, function ($record) use ($oldPhone, $newPhone, &$count) {
            $updated = $this->replaceInList($record->reminder_phones, $oldPhone, $newPhone);
            if ($updated === null) {
                return;
            }

            $record->reminder_phones = $updated === '' ? null : $updated;
            $record->save();
            $count++;
        });

        return $count;
    }

    protected function eachRecord(Builder $query, callable $callback): void
    {
        $query->orderBy($query->getModel()->getQualifiedKeyName())->chunkById(200, function ($records) use ($callback) {
            foreach ($records as $record) {
                $callback($record);
            }
        });
    }
}
