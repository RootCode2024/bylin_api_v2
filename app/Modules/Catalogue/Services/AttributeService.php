<?php

declare(strict_types=1);

namespace Modules\Catalogue\Services;

use Modules\Catalogue\Models\Attribute;
use Modules\Catalogue\Models\AttributeValue;
use Modules\Core\Services\BaseService;

/**
 * Attribute Service
 * 
 * Handles product attributes (Color, Size, etc.)
 */
class AttributeService extends BaseService
{
    /**
     * Create attribute
     */
    public function createAttribute(array $data): Attribute
    {
        return $this->transaction(function () use ($data) {
            $attribute = Attribute::create($data);
            
            if (!empty($data['values'])) {
                foreach ($data['values'] as $value) {
                    $attribute->values()->create($value);
                }
            }

            $this->logInfo('Attribute created', ['attribute_id' => $attribute->id]);

            return $attribute;
        });
    }

    /**
     * Update attribute
     */
    public function updateAttribute(string $id, array $data): Attribute
    {
        return $this->transaction(function () use ($id, $data) {
            $attribute = Attribute::findOrFail($id);
            $attribute->update($data);

            if (isset($data['values'])) {
                // Sync values logic could be complex (update existing, create new, delete missing)
                // For simplicity, we might just add new ones or update specific ones
                // Here is a simple implementation:
                foreach ($data['values'] as $valueData) {
                    if (isset($valueData['id'])) {
                        $val = AttributeValue::find($valueData['id']);
                        if ($val && $val->attribute_id === $attribute->id) {
                            $val->update($valueData);
                        }
                    } else {
                        $attribute->values()->create($valueData);
                    }
                }
            }

            $this->logInfo('Attribute updated', ['attribute_id' => $attribute->id]);

            return $attribute;
        });
    }

    /**
     * Delete attribute
     */
    public function deleteAttribute(string $id): bool
    {
        return $this->transaction(function () use ($id) {
            $attribute = Attribute::findOrFail($id);
            $attribute->delete();
            return true;
        });
    }
}
