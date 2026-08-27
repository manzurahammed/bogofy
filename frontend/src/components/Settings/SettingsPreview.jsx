import React from 'react';
import { __ } from '@wordpress/i18n';
import { GiftIcon } from '../Icons';

function SettingsPreview( { formData } ) {
	return (
		<div>
			<div className="bogo-section-header" style={{ margin: '0 0 12px' }}>
				<div className="bogo-section-header__title">{__( 'Preview', 'bogofy' )}</div>
				<div
					className="bogo-section-header__meta">{__( 'How shoppers will see it', 'bogofy' )}</div>
			</div>
			<div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 14 }}>
				<div className="bogo-panel" style={{ padding: 18 }}>
					<div style={{
						fontSize: 11.5,
						color: 'var(--muted)',
						marginBottom: 10
					}}>{__( 'Product card badge', 'bogofy' )}</div>
					<div style={{ borderRadius: 10, overflow: 'hidden', border: '1px solid var(--line)' }}>
						<div style={{
							height: 100,
							background: 'linear-gradient(135deg, #f0d3c8, #e0a78c)',
							position: 'relative'
						}}>
                    <span style={{
	                    position: 'absolute', top: 10, left: 10,
	                    background: 'var(--accent)', color: '#003a23',
	                    fontSize: 10.5, fontWeight: 700, padding: '4px 10px',
	                    borderRadius: 999, letterSpacing: '0.04em',
                    }}>
                      {formData.free_item_label || __( 'FREE (Bogofy Deal)', 'bogofy' )}
                    </span>
						</div>
						<div style={{ padding: 12 }}>
							<div style={{
								fontSize: 13,
								fontWeight: 500
							}}>{__( 'Example Product', 'bogofy' )}</div>
							<div className="bogo-mono"
							     style={{ fontSize: 12, color: 'var(--muted)', marginTop: 3 }}>$48.00
							</div>
						</div>
					</div>
				</div>
				<div className="bogo-panel" style={{ padding: 18 }}>
					<div style={{
						fontSize: 11.5,
						color: 'var(--muted)',
						marginBottom: 10
					}}>{__( 'Cart notice', 'bogofy' )}</div>
					<div style={{
						background: 'var(--accent-soft)',
						padding: 12,
						borderRadius: 10,
						display: 'flex',
						gap: 10,
						alignItems: 'center'
					}}>
						<GiftIcon size={18} stroke="var(--accent-ink)"/>
						<div style={{
							fontSize: 13,
							fontWeight: 600,
							color: 'var(--accent-ink)',
							lineHeight: 1.4
						}}>
							{formData.cart_notice_text || __( 'Congratulations! You got a free item!', 'bogofy' )}
						</div>
					</div>
				</div>
			</div>
		</div>
	);
}

export default SettingsPreview;
