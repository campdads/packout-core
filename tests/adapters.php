<?php
/**
 * Adapters that do nothing, for building a kernel in a test.
 *
 * @package PackOut_Core
 */

use PackOut\Core\Contracts;

class Test_Access implements Contracts\Access {
	public function can_read( int $post_id, int $user_id ): bool {
		return true;
	}
}
class Test_Lists implements Contracts\Lists {
	public function assets(): array {
		return array(
			'scripts' => array(),
			'styles'  => array(),
		);
	}
	public function saved( string $view, array $fields ): ?array {
		return null;
	}
}
class Test_Report_Form implements Contracts\Report_Form {
	public function available(): bool {
		return false;
	}
	public function render( array $args = array() ): string {
		return '';
	}
	public function selectors(): array {
		return array(
			'latitude'  => '',
			'longitude' => '',
			'photo'     => '',
			'row'       => '',
		);
	}
	public function photo_limit(): int {
		return 0;
	}
	public function status(): array {
		return array(
			'ok'     => false,
			'detail' => '',
		);
	}
	public function settings( array $settings ): array {
		return array(
			'intro' => '',
			'rows'  => '',
		);
	}
	public function entry_note( int $entry ): string {
		return '';
	}
}
class Test_Roles implements Contracts\Roles {
	public function register( array $capabilities, array $roles ): void {}
}
class Test_Storage implements Contracts\Storage {
	public function columns( string $post_type, array $meta_keys ): void {}
}
class Test_Screens implements Contracts\Screens {
	public function assets(): array {
		return array(
			'scripts' => array(),
			'styles'  => array(),
		);
	}
	public function supports( string $component ): bool {
		return false;
	}
	public function render( string $component, array $props = array(), string $content = '' ): string {
		return $content;
	}
}
class Test_Palette implements Contracts\Palette {
	public function colors(): array {
		return array_fill_keys( \PackOut\Core\Kernel::COLORS, '#000000' );
	}
}
class Test_Icons implements Contracts\Icons {
	public function svg( string $name, string $classes = '' ): string {
		return '';
	}
}
class Test_Settings implements Contracts\Settings {
	public $values = array();
	public function all(): array {
		return $this->values;
	}
	public function get( string $key, $fallback = null ) {
		return $this->values[ $key ] ?? $fallback;
	}
	public function update( array $values ): bool {
		$this->values = array_merge( $this->values, $values );
		return true;
	}
	public function option(): string {
		return 'packout_test_settings';
	}
}
class Test_Mailer implements Contracts\Mailer {
	public function send( string $to, string $subject, string $message, array $headers = array(), string $plain = '' ): bool {
		return true;
	}
}
class Test_Site implements Contracts\Site {
	public $surface = 'partner';
	public $phase   = 2;
	public function plugin(): string {
		return 'hub' === $this->surface ? 'packout-spot' : 'packout-partner';
	}
	public function version(): string {
		return '0.0.1';
	}
	public function surface(): string {
		return $this->surface;
	}
	public function phase(): int {
		return $this->phase;
	}
	public function is_hub(): bool {
		return 'hub' === $this->surface;
	}
	public function name(): string {
		return 'Test site';
	}
	public function mark(): string {
		return '';
	}
	public function base( string $kind ): string {
		return $kind;
	}
}

/**
 * One of each adapter.
 *
 * @return array<string, object>
 */
function test_adapters(): array {
	return array(
		'access'      => new Test_Access(),
		'lists'       => new Test_Lists(),
		'report_form' => new Test_Report_Form(),
		'roles'       => new Test_Roles(),
		'storage'     => new Test_Storage(),
		'screens'     => new Test_Screens(),
		'palette'     => new Test_Palette(),
		'icons'       => new Test_Icons(),
		'settings'    => new Test_Settings(),
		'mailer'      => new Test_Mailer(),
		'site'        => new Test_Site(),
	);
}
