<?php

class Shipday_Time_Slot_Util {

    public static function custom_time_slot_row( $field ) {
        $id    = $field['id'];
        $value = (array) get_option( $id, [ 'hh' => '09', 'mm' => '00', 'ampm' => 'AM' ] );

        $hh   = isset( $value['hh'] )   ? esc_attr( $value['hh'] )   : '09';
        $mm   = isset( $value['mm'] )   ? esc_attr( $value['mm'] )   : '00';
        $ampm = isset( $value['ampm'] ) ? esc_attr( $value['ampm'] ) : 'AM';

        echo '<tr valign="top"><th scope="row" class="titledesc"><label for="' . esc_attr( $id ) . '_hh">'
            . esc_html( $field['title'] ) . '</label></th><td class="forminp">';

        // Simple inline layout (HH : MM [AM/PM])
        echo '<div style="display:flex;align-items:center;gap:8px;">';

        // HH
        echo '<input type="text" id="' . esc_attr( $id ) . '_hh" name="' . esc_attr( $id ) . '[hh]" '
            . 'value="' . $hh . '" size="2" maxlength="2" min="1" max="12" step="1" inputmode="numeric" pattern="\d{2}" '
            . 'placeholder="HH" style="min-width:120px;text-align:center;" />';

        echo '<span>:</span>';

        // MM
        echo '<input type="text" id="' . esc_attr( $id ) . '_mm" name="' . esc_attr( $id ) . '[mm]" '
            . 'value="' . $mm . '" size="2" maxlength="2" inputmode="numeric" pattern="\d{2}" '
            . 'placeholder="MM" style="min-width:120px;text-align:center;" />';

        // AM/PM
        echo '<select id="' . esc_attr( $id ) . '_ampm" name="' . esc_attr( $id ) . '[ampm]" '
            . 'style="max-width:130px;">'
            . '<option value="AM"' . selected( $ampm, 'AM', false ) . '>AM</option>'
            . '<option value="PM"' . selected( $ampm, 'PM', false ) . '>PM</option>'
            . '</select>';

        echo '</div>';

        if ( ! empty( $field['desc'] ) ) {
            echo '<p class="description">' . wp_kses_post( $field['desc'] ) . '</p>';
        }

        echo '</td></tr>';
    }

    public static function sanitize_save_time_slot( $value, $option, $raw ) {
        // Expecting array: ['hh'=>'..','mm'=>'..','ampm'=>'AM|PM']
        $raw   = is_array( $raw ) ? $raw : [];
        $hh    = isset( $raw['hh'] )   ? (int) $raw['hh'] : 9;
        $mm    = isset( $raw['mm'] )   ? (int) $raw['mm'] : 0;
        $ampm  = ( isset( $raw['ampm'] ) && strtoupper( $raw['ampm'] ) === 'PM' ) ? 'PM' : 'AM';

        // Clamp & normalize (12-hour clock)
        if ( $hh < 1 )  { $hh = 1; }
        if ( $hh > 12 ) { $hh = 12; }
        if ( $mm < 0 )  { $mm = 0; }
        if ( $mm > 59 ) { $mm = 59; }

        return [
            'hh'   => str_pad( (string) $hh, 2, '0', STR_PAD_LEFT ),
            'mm'   => str_pad( (string) $mm, 2, '0', STR_PAD_LEFT ),
            'ampm' => $ampm,
        ];
    }

    public static function custom_section_text( $field ) {
        $text = isset( $field['text'] ) ? $field['text'] : '';
        echo '<tr valign="top"><td colspan="2" style="padding:0;">';
        echo '<hr style="border:0;border-top:1px solid #ddd;margin:20px 0;">';
        if ( $text !== '' ) {
            echo '<div style="text-align:center;color:#555;margin:8px 0 6px;font-weight:bold;font-size:18px;">'
                . wp_kses_post( $text )
                . '</div>';
        }
        echo '</td></tr>';
    }

}
