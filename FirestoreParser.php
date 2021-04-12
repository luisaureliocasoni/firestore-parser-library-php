<?php



class FirestoreParser
{

    private static function getFirestoreProp($value)
    {
        $props = [
            'arrayValue' => 1,
            'bytesValue' => 1,
            'booleanValue' => 1,
            'doubleValue' => 1,
            'geoPointValue' => 1,
            'integerValue' => 1,
            'mapValue' => 1,
            'nullValue' => 1,
            'referenceValue' => 1,
            'stringValue' => 1,
            'timestampValue' => 1
        ];

        $keys = is_array($value) ? array_keys($value) : [];

        foreach ($keys as $key) {
            if (isset($props[$key]) && $props[$key] === 1){
                // encontrou
                return $key;
            }
        }

        return null;
    }

    /***
     * Faz o parse de um array de dados do firebase e transforma em um array comum
     * @param $value
     * @return array|array[]|float|float[]|int|int[]|mixed
     */
    public static function parse($value)
    {
        $prop = self::getFirestoreProp($value);

        switch ($prop){
            case 'doubleValue':
                $value = (double) $value[$prop];
                break;
            case 'integerValue':
                $value = (int) $value[$prop];
                break;
            case 'arrayValue':
                $array = is_array($value[$prop]) && isset($value[$prop]['values']) ? $value[$prop]['values'] : [];
                $value = array_map(function ($v) {
                    return self::parse($v);
                }, $array);
                break;
            case 'mapValue':
                $array = is_array($value[$prop]) && isset($value[$prop]['fields']) ? $value[$prop]['fields'] : [];
                $value = self::parse($array);
                break;
            case 'geoPointValue':
                $value = array_merge([
                    'latitude' => 0,
                    'longitude' => 0,
                ], $value[$prop]);
                break;
            default:
                if ($prop) {
                    $value = $value[$prop];
                } else if (is_array($value)) {
                    $keys = array_keys($value);
                    foreach ($keys as $key) {
                        $value[$key] = self::parse($value[$key]);
                    }
                }
        }

        return $value;
    }

    private static function serializeValue($value) {
        $type = gettype($value);

        if ($type === 'float' || $type === 'double') {
            return ['doubleValue' => $value];
        } else if ($type === 'integer') {
            return ['integerValue' => $value];
        } else if ($type === 'boolean') {
            return ['booleanValue' => $value];
        } else if ($type === 'array') {
            $arr = [];
            foreach ($value as $key => $val) {
                $arr[$key] = self::serializeValue($val);
            }
            return [
                'mapValue' => [
                    'fields' => $arr
                ]
            ];
        }else if (is_null($value)){
            return ['nullValue' => null];
        } else if (preg_match('/(\d{4})-(\d{2})-(\d{2})T(\d{2})\:(\d{2})\:(\d{2})[+-](\d{2})\:(\d{2})/', $value)) {
            return ['timestampValue' => $value];
        } else {
            return ['stringValue' => $value];
        }

    }


    /**
     * Faz a serialização de dados de um array comum para um array formato do Firebase
     * @param $values
     * @return array[]
     */
    public static function serialize($values)
    {
        $return = [
            'fields' => []
        ];

        foreach ($values as $key => $value) {
            $return['fields'][$key] = self::serializeValue($value);
        }

        return $return;
    }

}
