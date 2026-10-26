<?php
declare(strict_types=1);

use Loopress\Hooks\Attribute\Action;

/**
 * [bakery_hours] and [bakery_phone]: the opening hours and phone number of the `bakery_info`
 * option (options/bakery_info.json), so pages and footer never repeat them. Settings > Bakery
 * info lets the owner edit them in wp-admin, the edit a push must not overwrite.
 */
final class BakeryInfo
{
    private const FIELDS = [
        'closed_day' => 'Closed on',
        'weekday_hours' => 'Tuesday to Saturday',
        'sunday_hours' => 'Sunday',
        'phone' => 'Phone',
    ];

    #[Action('admin_init')]
    public function registerSetting(): void
    {
        register_setting('bakery_info', 'bakery_info', [
            'type' => 'object',
            'sanitize_callback' => fn ($value): array => array_map(
                fn (string $key): string => sanitize_text_field((string) ($value[$key] ?? '')),
                array_combine(array_keys(self::FIELDS), array_keys(self::FIELDS)),
            ),
        ]);
    }

    #[Action('admin_menu')]
    public function addSettingsPage(): void
    {
        add_options_page('Bakery info', 'Bakery info', 'manage_options', 'bakery-info', [$this, 'renderSettingsPage']);
    }

    public function renderSettingsPage(): void
    {
        echo '<div class="wrap"><h1>Bakery info</h1><form method="post" action="options.php">';
        settings_fields('bakery_info');
        echo '<table class="form-table">';
        foreach (self::FIELDS as $key => $label) {
            printf(
                '<tr><th><label for="bakery-%1$s">%2$s</label></th><td><input id="bakery-%1$s" name="bakery_info[%1$s]" value="%3$s" class="regular-text"></td></tr>',
                esc_attr($key),
                esc_html($label),
                esc_attr($this->info($key)),
            );
        }
        echo '</table>';
        submit_button();
        echo '</form></div>';
    }

    #[Action('init')]
    public function registerShortcodes(): void
    {
        add_shortcode('bakery_hours', [$this, 'hours']);
        add_shortcode('bakery_phone', fn (): string => esc_html($this->info('phone')));
    }

    /** `[bakery_hours]` is a table, `[bakery_hours format="inline"]` one sentence. */
    public function hours(array|string $atts = []): string
    {
        $atts = shortcode_atts(['format' => 'table'], $atts);
        $closed = esc_html($this->info('closed_day'));
        $weekdays = esc_html($this->info('weekday_hours'));
        $sunday = esc_html($this->info('sunday_hours'));

        if ($atts['format'] === 'inline') {
            return "Tuesday to Saturday {$weekdays}, Sunday {$sunday}. Closed on {$closed}.";
        }

        return '<table class="c-hours">'
            . "<tr><th>{$closed}</th><td>Closed</td></tr>"
            . "<tr><th>Tuesday to Saturday</th><td>{$weekdays}</td></tr>"
            . "<tr><th>Sunday</th><td>{$sunday}</td></tr>"
            . '</table>';
    }

    private function info(string $key): string
    {
        $info = get_option('bakery_info', []);

        return is_array($info) ? (string) ($info[$key] ?? '') : '';
    }
}
