<?php

namespace App\Support;

/**
 * Step 4 vision / pulse sub-field keys (each stored and rendered separately on the PDF).
 */
class Step4FieldComposer
{
    /**
     * @return list<string>
     */
    public static function pulseKeys(): array
    {
        return [
            'pulse_sitting',
            'pulse_after_exercise',
            'pulse_2min_after',
            'pulse_recumbent',
            'pulse_standing_3min',
        ];
    }

    /**
     * @return list<string>
     */
    public static function refractionKeys(): array
    {
        $keys = [];
        foreach (['right', 'left'] as $side) {
            foreach (['by', 's', 'cx'] as $part) {
                $keys[] = "refraction_{$side}_{$part}";
            }
        }

        return $keys;
    }

    /**
     * @return list<string>
     */
    public static function nearVisionKeys(): array
    {
        $keys = [];
        foreach (['right', 'left'] as $side) {
            $keys[] = "near_vision_{$side}_color_to";
            $keys[] = "near_vision_{$side}_by";
        }

        return $keys;
    }

    /**
     * @return list<string>
     */
    public static function heterophoriaKeys(): array
    {
        return [
            'heterophoria_distance',
            'heterophoria_es',
            'heterophoria_ex',
            'heterophoria_rh',
            'heterophoria_lh',
            'heterophoria_prism',
            'heterophoria_dv',
            'heterophoria_conv',
            'heterophoria_pc',
            'heterophoria_pd',
        ];
    }

    /**
     * @return list<string>
     */
    public static function depthPerceptionKeys(): array
    {
        return [
            'depth_uncorrected_test_used',
            'depth_uncorrected_score',
            'depth_corrected_test_used',
            'depth_corrected_score',
        ];
    }

    /**
     * @return list<string>
     */
    public static function allSubfieldKeys(): array
    {
        return array_merge(
            self::refractionKeys(),
            self::nearVisionKeys(),
            self::heterophoriaKeys(),
            self::depthPerceptionKeys(),
        );
    }

    /**
     * Legacy combined keys used before sub-fields were split.
     *
     * @return list<string>
     */
    public static function legacyCombinedKeys(): array
    {
        return [
            'refraction_right',
            'refraction_left',
            'near_vision_right',
            'near_vision_left',
            'heterophoria',
            'depth_perception',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $subKeys
     */
    public static function subfieldsEmpty(array $data, array $subKeys): bool
    {
        foreach ($subKeys as $key) {
            if (trim((string) ($data[$key] ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }
}
