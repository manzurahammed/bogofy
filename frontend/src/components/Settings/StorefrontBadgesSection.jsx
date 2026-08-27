import React from 'react';
import { __ } from '@wordpress/i18n';
import SettingsRow from './SettingsRow';

function ToggleButton( { on, onClick } ) {
	return (
		<button
			type="button"
			className={`bogo-toggle${on ? ' bogo-toggle--on' : ''}`}
			onClick={onClick}
		/>
	);
}

function StorefrontBadgesSection( { formData, onToggle, onChange } ) {
	return (
		<div>
			<div className="bogo-section-header" style={{ margin: '0 0 12px' }}>
				<div className="bogo-section-header__title">{__( 'Storefront badges', 'bogofy' )}</div>
				<div className="bogo-section-header__meta">{__( 'Visible to shoppers', 'bogofy' )}</div>
			</div>
			<div className="bogo-settings-list">
				<SettingsRow
					name={__( 'Enable plugin', 'bogofy' )}
					desc={__( 'Turn all Bogofy functionality on or off globally.', 'bogofy' )}
				>
					<ToggleButton on={formData.enabled} onClick={() => onToggle( 'enabled' )}/>
				</SettingsRow>
				<SettingsRow
					name={__( 'Show “Bogofy” badge on product cards', 'bogofy' )}
					desc={__( 'Highlights eligible products in shop & category pages.', 'bogofy' )}
				>
					<ToggleButton on={formData.show_shop_badges} onClick={() => onToggle( 'show_shop_badges' )}/>
				</SettingsRow>
				<SettingsRow
					name={__( 'Show product page messages', 'bogofy' )}
					desc={__( 'Display Bogofy offer messages on individual product pages.', 'bogofy' )}
				>
					<ToggleButton on={formData.show_product_page_messages}
					              onClick={() => onToggle( 'show_product_page_messages' )}/>
				</SettingsRow>
				<SettingsRow
					name={__( 'Free item label', 'bogofy' )}
					desc={__( 'Label shown next to free items in cart.', 'bogofy' )}
				>
					<input
						className="bogo-form-input"
						name="free_item_label"
						value={formData.free_item_label}
						onChange={onChange}
						style={{ width: 260 }}
					/>
				</SettingsRow>
			</div>
		</div>
	);
}

export default StorefrontBadgesSection;
