const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { createElement } = window.wp.element;
const { __ } = window.wp.i18n;
const { getSetting } = window.wc.wcSettings;

const settings = getSetting('tapsilat_data', {});
const label = settings.title || __('Payment by Tapsilat', 'tapsilat-woocommerce');

// Create label with large icon and title/description layout
const LabelWithIcon = () => {
    if (settings.icon) {
        return createElement('div', {
            style: { 
                display: 'flex', 
                alignItems: 'center', 
                gap: '12px',
                padding: '8px 0'
            }
        }, [
            // Large logo (2 line height)
            createElement('img', {
                key: 'icon',
                src: settings.icon,
                alt: 'Tapsilat',
                style: { 
                    width: '48px', 
                    height: '32px', 
                    objectFit: 'contain',
                    flexShrink: 0
                }
            }),
            // Title and description stacked
            createElement('div', {
                key: 'content',
                style: {
                    display: 'flex',
                    flexDirection: 'column',
                    justifyContent: 'center',
                    gap: '2px'
                }
            }, [
                createElement('div', {
                    key: 'title',
                    style: {
                        fontWeight: '600',
                        fontSize: '14px',
                        lineHeight: '1.2',
                        color: '#374151'
                    }
                }, label),
                createElement('div', {
                    key: 'description',
                    style: {
                        fontSize: '12px',
                        lineHeight: '1.2',
                        color: '#6B7280'
                    }
                }, settings.description || __('Pay securely using your credit/debit card or alternative payment methods.', 'tapsilat-woocommerce'))
            ])
        ]);
    }
    return label;
};

const Content = () => {
    // Return empty content since we show description in the label
    return createElement('div', {
        className: 'wc-block-components-payment-method-content',
        style: { display: 'none' }
    });
};

const TapsilatPaymentMethod = {
    name: 'tapsilat',
    label: createElement(LabelWithIcon),
    content: createElement(Content),
    edit: createElement(Content),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports || [],
    },
};

registerPaymentMethod(TapsilatPaymentMethod);