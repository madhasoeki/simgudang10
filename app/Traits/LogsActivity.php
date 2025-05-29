<?php

namespace App\Traits;

use App\Models\History;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function (Model $model) {
            static::logActivity($model, 'CREATE', null, $model->getAttributes());
        });

        // Variabel untuk menyimpan nilai lama sementara sebelum update
        // Ini akan diakses dalam closure, jadi kita gunakan 'use'
        $originalAttributesOnUpdate = [];

        static::updating(function (Model $model) use (&$originalAttributesOnUpdate) {
            // Simpan nilai asli dari atribut yang akan diubah
            // Fokus pada atribut yang fillable atau yang relevan
            $originalFillableValues = [];
            foreach ($model->getFillable() as $fillableAttribute) {
                if (array_key_exists($fillableAttribute, $model->getOriginal())) {
                    $originalFillableValues[$fillableAttribute] = $model->getOriginal($fillableAttribute);
                }
            }
            // Simpan ke variabel yang bisa diakses oleh event 'updated'
            $originalAttributesOnUpdate = $originalFillableValues;
        });

        static::updated(function (Model $model) use (&$originalAttributesOnUpdate) {
            $newValues = $model->getChanges();
            unset($newValues['updated_at']); // Jangan catat perubahan updated_at

            if (empty($newValues)) {
                return; // Tidak ada perubahan signifikan untuk di-log
            }

            $oldValuesFiltered = [];
            if (!empty($originalAttributesOnUpdate)) {
                 foreach (array_keys($newValues) as $key) {
                    if (array_key_exists($key, $originalAttributesOnUpdate)) {
                        $oldValuesFiltered[$key] = $originalAttributesOnUpdate[$key];
                    }
                }
            }
            
            static::logActivity($model, 'UPDATE', $oldValuesFiltered, $newValues);
            
            // Reset variabel setelah digunakan
            $originalAttributesOnUpdate = []; 
        });

        static::deleted(function (Model $model) {
            static::logActivity($model, 'DELETE', $model->getAttributes(), null);
        });
    }

    /**
     * Method untuk mencatat aktivitas.
     *
     * @param Model $model
     * @param string $action
     * @param array|null $oldValues
     * @param array|null $newValues
     */
    protected static function logActivity(Model $model, string $action, ?array $oldValues = null, ?array $newValues = null)
    {
        if (!Auth::check() && $action !== 'SEED') {
            // return; // Atau tentukan user_id default untuk sistem
        }

        $recordId = (string) $model->getKey();
        if (is_array($model->getKey())) {
            $recordId = implode('-', $model->getKey());
        }

        History::create([
            'user_id'    => Auth::id(),
            'table_name' => $model->getTable(),
            'record_id'  => $recordId,
            'action'     => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'created_at' => now(config('app.timezone'))
        ]);
    }
}