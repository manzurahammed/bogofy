import React, { useState } from 'react';
import { useCreateRule } from '../hooks/useRules';
import { useNotification } from '../hooks/useNotification';
import AppShell from '../components/Layout/AppShell';
import { ProductSearch } from '../components/Shared/ProductSearch';
import { CategorySearch } from '../components/Shared/CategorySearch';
import {
  CheckIcon, ArrowIcon, BoxIcon, GiftIcon, PercentIcon,
  LayersIcon, EyeIcon, PowerIcon,
} from '../components/Icons';

const ruleTypes = [
  { value: 'buy_x_get_x', label: 'Buy X, Get same free', desc: 'Cheapest of N identical items becomes free.', vis: 'same' },
  { value: 'buy_x_get_y', label: 'Buy X, Get Y free', desc: 'Customer adds trigger item; gift drops to $0.', vis: 'gift' },
  { value: 'buy_x_get_x_discounted', label: 'Buy X, Get X at % off', desc: 'Apply a percentage discount on the second item.', vis: 'pct' },
  { value: 'buy_cat_get_free', label: 'Cross-category BOGO', desc: 'Buy from Category A → get from Category B.', vis: 'cat' },
];

const defaultData = {
  title: '',
  rule_type: 'buy_x_get_y',
  status: 'active',
  buy_quantity: 1,
  free_quantity: 1,
  discount_type: 'free',
  discount_value: 100,
  apply_to: 'specific_products',
  buy_product_ids: [],
  free_product_ids: [],
  category_ids: [],
  max_free_qty: '',
  message_template: '',
  priority: 10,
  start_date: '',
  end_date: '',
};

function Stepper({ active }) {
  const steps = ['Offer type', 'Products', 'Conditions', 'Review'];
  return (
    <div className="bogo-stepper">
      {steps.map((s, i) => {
        const n = i + 1;
        const state = n < active ? 'bogo-stepper__step--done' : n === active ? 'bogo-stepper__step--active' : '';
        return (
          <React.Fragment key={i}>
            <div className={`bogo-stepper__step ${state}`}>
              <span className="bogo-stepper__num">
                {n < active ? <CheckIcon size={12} sw={2.5} /> : n}
              </span>
              <span>{s}</span>
            </div>
            {i < steps.length - 1 && <div className="bogo-stepper__sep" />}
          </React.Fragment>
        );
      })}
    </div>
  );
}

function CartPreview({ formData, selectedBuyProducts, selectedFreeProducts, step }) {
  return (
    <div className="bogo-wizard__card" style={{ padding: 0, overflow: 'hidden' }}>
      <div className="bogo-row" style={{ padding: '14px 18px', borderBottom: '1px solid var(--line)', gap: 8 }}>
        <EyeIcon size={14} stroke="var(--muted)" />
        <span style={{ fontSize: 12.5, fontWeight: 600 }}>Live preview</span>
        <span style={{ marginLeft: 'auto', color: 'var(--muted)', fontSize: 11.5 }}>Cart view</span>
      </div>
      <div style={{ padding: 16 }}>
        <div className="bogo-preview__cart">
          {selectedBuyProducts.slice(0, 2).map((p, i) => (
            <div key={i} className="bogo-preview__line">
              <div className="bogo-thumb" />
              <div className="bogo-col">
                <div className="bogo-preview__name">{p.name}</div>
                <div className="bogo-preview__price">Qty 1</div>
              </div>
              <span className="bogo-mono" style={{ fontSize: 12 }}>—</span>
            </div>
          ))}
          {selectedBuyProducts.length === 0 && (
            <div className="bogo-preview__line">
              <div className="bogo-thumb" />
              <div className="bogo-col">
                <div className="bogo-preview__name">Trigger product</div>
                <div className="bogo-preview__price">Qty {formData.buy_quantity}</div>
              </div>
              <span className="bogo-mono" style={{ fontSize: 12 }}>—</span>
            </div>
          )}
          {step >= 2 && selectedFreeProducts.length > 0 && (
            <div className="bogo-preview__line" style={{ background: 'var(--accent-soft)', margin: '0 -14px', padding: '8px 14px' }}>
              <div className="bogo-thumb" style={{ background: 'linear-gradient(135deg,#fbcfe8,#f0abfc)' }} />
              <div className="bogo-col">
                <div className="bogo-preview__name">
                  {selectedFreeProducts[0]?.name || 'Gift product'}{' '}
                  <span className="bogo-free-badge">FREE</span>
                </div>
                <div className="bogo-preview__price" style={{ color: 'var(--accent-ink)' }}>BOGO discount</div>
              </div>
              <div className="bogo-col bogo-align-right">
                <span className="bogo-mono" style={{ fontSize: 12, fontWeight: 600, color: 'var(--accent-ink)' }}>−$0.00</span>
              </div>
            </div>
          )}
          <div style={{ padding: '10px 0 0', borderTop: '1px solid var(--line)', marginTop: 4 }}>
            <div className="bogo-row" style={{ justifyContent: 'space-between', fontSize: 14, fontWeight: 700, marginTop: 4 }}>
              <span>Total</span>
              <span className="bogo-mono">—</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

function SummaryRail({ formData, step, selectedBuyProducts, selectedFreeProducts }) {
  const SumRow = ({ l, v, done }) => (
    <div className="bogo-summary-row">
      <span style={{ color: 'var(--muted)' }}>{l}</span>
      <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, color: done ? 'var(--ink)' : 'var(--muted-2)', fontWeight: done ? 500 : 400 }}>
        {done && <span style={{ width: 5, height: 5, borderRadius: 999, background: 'var(--accent)' }} />}
        {v}
      </span>
    </div>
  );

  return (
    <div className="bogo-wizard__card">
      <div style={{ fontSize: 12.5, fontWeight: 600, marginBottom: 10 }}>Summary</div>
      <div className="bogo-col" style={{ gap: 9 }}>
        <SumRow l="Type" v={ruleTypes.find((r) => r.value === formData.rule_type)?.label || '—'} done={step >= 1} />
        <SumRow l="Name" v={formData.title || 'Not set'} done={!!formData.title} />
        <SumRow l="Trigger" v={selectedBuyProducts.length > 0 ? `${selectedBuyProducts.length} products · min ${formData.buy_quantity}` : 'Not set'} done={selectedBuyProducts.length > 0} />
        <SumRow l="Gift" v={selectedFreeProducts.length > 0 ? `${selectedFreeProducts.length} products` : 'Not set'} done={selectedFreeProducts.length > 0} />
        <SumRow l="Schedule" v={formData.start_date ? `${formData.start_date.slice(0, 10)}` : 'Always on'} done={!!formData.start_date} />
      </div>
    </div>
  );
}

// Step 1: Offer type
function Step1({ formData, setFormData }) {
  return (
    <div className="bogo-wizard__card">
      <div className="bogo-wizard__title">Choose offer type</div>
      <div className="bogo-wizard__subtitle">The structure of your rule — determines what other steps look like.</div>
      <div className="bogo-type-grid">
        {ruleTypes.map((t) => (
          <div
            key={t.value}
            className={`bogo-type-card${formData.rule_type === t.value ? ' bogo-type-card--selected' : ''}`}
            onClick={() => setFormData((p) => ({ ...p, rule_type: t.value }))}
          >
            <div className="bogo-type-card__visual">
              <div className="bogo-pkg"><BoxIcon size={14} /></div>
              <span style={{ color: 'var(--muted-2)', fontSize: 12 }}>→</span>
              <div className={`bogo-pkg${formData.rule_type === t.value ? ' bogo-pkg--get' : ''}`}>
                {t.vis === 'gift' && <GiftIcon size={14} />}
                {t.vis === 'pct' && <PercentIcon size={14} />}
                {t.vis === 'same' && <BoxIcon size={14} />}
                {t.vis === 'cat' && <LayersIcon size={14} />}
              </div>
            </div>
            <div>
              <div className="bogo-type-card__title">{t.label}</div>
              <div className="bogo-type-card__desc">{t.desc}</div>
            </div>
          </div>
        ))}
      </div>

      <div style={{ marginTop: 22, display: 'grid', gridTemplateColumns: '1fr 180px', gap: 14 }}>
        <div>
          <label className="bogo-form-label">Rule name</label>
          <input
            className="bogo-form-input"
            placeholder="e.g., Summer Swim — Buy 2 Get 1"
            value={formData.title}
            onChange={(e) => setFormData((p) => ({ ...p, title: e.target.value }))}
          />
        </div>
        <div>
          <label className="bogo-form-label">Priority</label>
          <input
            type="number"
            className="bogo-form-input bogo-mono"
            value={formData.priority}
            min="1"
            onChange={(e) => setFormData((p) => ({ ...p, priority: Number(e.target.value) }))}
          />
          <div className="bogo-form-hint">Lower = higher priority</div>
        </div>
      </div>
    </div>
  );
}

// Step 2: Products
function Step2({ formData, setFormData, selectedBuyProducts, setSelectedBuyProducts, selectedFreeProducts, setSelectedFreeProducts, selectedCategories, setSelectedCategories, errors }) {
  const showFreeSelector = formData.rule_type === 'buy_x_get_y' || formData.rule_type === 'buy_cat_get_free';

  return (
    <>
      <div className="bogo-wizard__card">
        <div className="bogo-wizard__title">Trigger products <span style={{ color: 'var(--muted)', fontWeight: 400, fontSize: 13 }}>· “Buy X”</span></div>
        <div className="bogo-wizard__subtitle">Customer must add these to cart for the offer to apply.</div>

        <div style={{ display: 'flex', gap: 6, marginTop: 16, marginBottom: 14, flexWrap: 'wrap' }}>
          {[
            { v: 'specific_products', l: 'Specific products' },
            { v: 'specific_categories', l: 'By category' },
            { v: 'all_products', l: 'Any product' },
          ].map((opt) => (
            <button
              key={opt.v}
              type="button"
              className={`bogo-chip${formData.apply_to === opt.v ? ' bogo-chip--selected' : ''}`}
              onClick={() => setFormData((p) => ({ ...p, apply_to: opt.v }))}
            >
              {formData.apply_to === opt.v && <CheckIcon size={11} />}
              {opt.l}
            </button>
          ))}
        </div>

        {formData.apply_to === 'specific_products' && (
          <div style={{ marginBottom: 14 }}>
            <ProductSearch
              selectedProducts={selectedBuyProducts}
              onChange={setSelectedBuyProducts}
              placeholder="Search for trigger products…"
            />
            {errors.buy_products && <div className="bogo-form-error">{errors.buy_products}</div>}
          </div>
        )}

        {formData.apply_to === 'specific_categories' && (
          <div style={{ marginBottom: 14 }}>
            <CategorySearch
              selectedCategories={selectedCategories}
              onChange={setSelectedCategories}
              placeholder="Search categories…"
            />
            {errors.categories && <div className="bogo-form-error">{errors.categories}</div>}
          </div>
        )}

        <div className="bogo-row" style={{ gap: 10, marginTop: 8 }}>
          <label className="bogo-form-label" style={{ margin: 0 }}>Minimum quantity to trigger</label>
          <input
            type="number"
            className="bogo-form-input"
            style={{ width: 70, textAlign: 'center' }}
            min="1"
            value={formData.buy_quantity}
            onChange={(e) => setFormData((p) => ({ ...p, buy_quantity: Number(e.target.value) }))}
          />
          <span style={{ color: 'var(--muted)', fontSize: 12 }}>items in cart</span>
        </div>
      </div>

      {showFreeSelector && (
        <div className="bogo-wizard__card">
          <div className="bogo-wizard__title">Gift products <span style={{ color: 'var(--muted)', fontWeight: 400, fontSize: 13 }}>· “Get Y”</span></div>
          <div className="bogo-wizard__subtitle">What customers receive when the trigger is met.</div>
          <div style={{ marginTop: 14 }}>
            <ProductSearch
              selectedProducts={selectedFreeProducts}
              onChange={setSelectedFreeProducts}
              placeholder="Search for gift products…"
            />
            {errors.free_products && <div className="bogo-form-error">{errors.free_products}</div>}
          </div>

          <div className="bogo-row" style={{ marginTop: 16, gap: 14, padding: 14, background: '#fafaf9', borderRadius: 10 }}>
            <GiftIcon size={18} stroke="var(--accent-ink)" />
            <div className="bogo-col bogo-fill">
              <div style={{ fontWeight: 600, fontSize: 13 }}>Discount applied</div>
              <div style={{ fontSize: 12, color: 'var(--muted)', marginTop: 2 }}>How much off the gift item.</div>
            </div>
            <div className="bogo-row" style={{ gap: 6 }}>
              <button
                type="button"
                className={`bogo-chip${formData.discount_type === 'free' ? ' bogo-chip--selected' : ''}`}
                onClick={() => setFormData((p) => ({ ...p, discount_type: 'free', discount_value: 100 }))}
              >100% off (Free)</button>
              <button
                type="button"
                className={`bogo-chip${formData.discount_type === 'percentage' ? ' bogo-chip--selected' : ''}`}
                onClick={() => setFormData((p) => ({ ...p, discount_type: 'percentage', discount_value: 50 }))}
              >Custom %</button>
            </div>
            {formData.discount_type === 'percentage' && (
              <input
                type="number"
                className="bogo-form-input"
                style={{ width: 80 }}
                min="1"
                max="100"
                value={formData.discount_value}
                onChange={(e) => setFormData((p) => ({ ...p, discount_value: Number(e.target.value) }))}
              />
            )}
          </div>

          <div className="bogo-row" style={{ gap: 10, marginTop: 14 }}>
            <label className="bogo-form-label" style={{ margin: 0 }}>Gift quantity</label>
            <input
              type="number"
              className="bogo-form-input"
              style={{ width: 70, textAlign: 'center' }}
              min="1"
              value={formData.free_quantity}
              onChange={(e) => setFormData((p) => ({ ...p, free_quantity: Number(e.target.value) }))}
            />
            <span style={{ color: 'var(--muted)', fontSize: 12 }}>items gifted</span>
          </div>
        </div>
      )}
    </>
  );
}

// Step 3: Conditions
function Step3({ formData, setFormData }) {
  return (
    <div className="bogo-wizard__card">
      <div className="bogo-wizard__title">When does this rule apply?</div>
      <div className="bogo-wizard__subtitle">All conditions must be met for the discount to activate.</div>

      <div className="bogo-conditions">
        {/* Schedule */}
        <div className="bogo-conditions__row">
          <div className="bogo-conditions__label">Schedule</div>
          <div className="bogo-conditions__body">
            <div style={{ width: '100%', display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap' }}>
              <input
                type="datetime-local"
                className="bogo-form-input"
                style={{ flex: 1, minWidth: 180 }}
                value={formData.start_date}
                onChange={(e) => setFormData((p) => ({ ...p, start_date: e.target.value }))}
              />
              <span style={{ color: 'var(--muted)' }}>→</span>
              <input
                type="datetime-local"
                className="bogo-form-input"
                style={{ flex: 1, minWidth: 180 }}
                value={formData.end_date}
                onChange={(e) => setFormData((p) => ({ ...p, end_date: e.target.value }))}
              />
            </div>
            <div className="bogo-form-hint">Leave blank for “always on”</div>
          </div>
        </div>

        {/* Usage limit */}
        <div className="bogo-conditions__row">
          <div className="bogo-conditions__label">Usage limit</div>
          <div className="bogo-conditions__body">
            <span style={{ fontSize: 12.5, color: 'var(--muted)' }}>Max free items</span>
            <input
              type="number"
              className="bogo-form-input"
              style={{ width: 90 }}
              min="1"
              value={formData.max_free_qty}
              placeholder="Unlimited"
              onChange={(e) => setFormData((p) => ({ ...p, max_free_qty: e.target.value }))}
            />
            <span style={{ fontSize: 12.5, color: 'var(--muted)' }}>per order</span>
          </div>
        </div>
      </div>

      {/* Display message */}
      <div style={{ marginTop: 22 }}>
        <label className="bogo-form-label">Product page message <span style={{ color: 'var(--muted)', fontWeight: 400 }}>(optional)</span></label>
        <input
          className="bogo-form-input"
          value={formData.message_template}
          onChange={(e) => setFormData((p) => ({ ...p, message_template: e.target.value }))}
          placeholder="e.g., Buy {buy_qty}, Get {free_qty} FREE!"
        />
        <div className="bogo-form-hint">Available: {'{buy_qty}'}, {'{free_qty}'}, {'{free_product}'}</div>
      </div>
    </div>
  );
}

// Step 4: Review
function Step4({ formData, selectedBuyProducts, selectedFreeProducts, onLaunch, onDraft, isLoading }) {
  const ReviewBlock = ({ title, step, children }) => (
    <div className="bogo-review-block">
      <div className="bogo-row" style={{ justifyContent: 'space-between', marginBottom: 8 }}>
        <span style={{ fontSize: 11.5, color: 'var(--muted)', textTransform: 'uppercase', letterSpacing: '0.04em', fontWeight: 500 }}>{title}</span>
        <span style={{ fontSize: 11, color: 'var(--muted)' }}>Step {step}</span>
      </div>
      {children}
    </div>
  );

  return (
    <div className="bogo-wizard__card">
      <div className="bogo-wizard__title">Review your rule</div>
      <div className="bogo-wizard__subtitle">Everything looks good? Launch it live or save as draft.</div>

      <div className="bogo-review-grid">
        <ReviewBlock title="Offer type" step="1">
          <div className="bogo-flow">
            <span className="bogo-flow__node"><BoxIcon size={12} /> Buy {formData.buy_quantity}</span>
            <span className="bogo-flow__arrow">→</span>
            <span className="bogo-flow__node bogo-flow__node--get"><GiftIcon size={12} /> Get {formData.free_quantity} {formData.discount_type === 'free' ? 'free' : `at ${formData.discount_value}% off`}</span>
          </div>
        </ReviewBlock>
        <ReviewBlock title="Name" step="1">
          <div style={{ fontWeight: 600, fontSize: 13 }}>{formData.title || '(Unnamed rule)'}</div>
          <div className="bogo-mono" style={{ color: 'var(--muted-2)', fontSize: 11.5, marginTop: 3 }}>Priority: {formData.priority}</div>
        </ReviewBlock>

        <ReviewBlock title="Trigger products" step="2">
          <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
            {selectedBuyProducts.length > 0
              ? selectedBuyProducts.map((p) => (
                  <span key={p.id} className="bogo-product-pill"><span className="bogo-thumb" />{p.name}</span>
                ))
              : <span style={{ color: 'var(--muted)', fontSize: 12 }}>All products ({formData.apply_to})</span>
            }
          </div>
          <div style={{ color: 'var(--muted)', fontSize: 11.5, marginTop: 8 }}>Min. qty: {formData.buy_quantity}</div>
        </ReviewBlock>
        <ReviewBlock title="Gift products" step="2">
          <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
            {selectedFreeProducts.length > 0
              ? selectedFreeProducts.map((p) => (
                  <span key={p.id} className="bogo-product-pill"><span className="bogo-thumb" />{p.name}</span>
                ))
              : <span style={{ color: 'var(--muted)', fontSize: 12 }}>—</span>
            }
          </div>
          <div style={{ color: 'var(--accent-ink)', fontSize: 11.5, marginTop: 8, fontWeight: 600 }}>
            {formData.discount_type === 'free' ? '100% off (Free)' : `${formData.discount_value}% off`}
          </div>
        </ReviewBlock>

        <ReviewBlock title="Schedule" step="3">
          <div className="bogo-mono" style={{ fontSize: 12 }}>
            {formData.start_date ? `${formData.start_date.slice(0, 10)}` : 'Always on'}
            {formData.end_date ? ` → ${formData.end_date.slice(0, 10)}` : ''}
          </div>
        </ReviewBlock>
        <ReviewBlock title="Limits" step="3">
          <div style={{ fontSize: 12.5 }}>
            {formData.max_free_qty ? `Max ${formData.max_free_qty} free items/order` : 'No limit'}
          </div>
        </ReviewBlock>
      </div>

      <div className="bogo-row" style={{ marginTop: 22, gap: 10, justifyContent: 'flex-end' }}>
        <button type="button" className="bogo-button" onClick={onDraft} disabled={isLoading}>
          Save as draft
        </button>
        <button type="button" className="bogo-button bogo-button--primary" onClick={onLaunch} disabled={isLoading}>
          <PowerIcon size={14} />
          {isLoading ? 'Saving…' : 'Launch rule'}
        </button>
      </div>
    </div>
  );
}

function CreateRulePage() {
  const [step, setStep] = useState(1);
  const [formData, setFormData] = useState(defaultData);
  const [selectedBuyProducts, setSelectedBuyProducts] = useState([]);
  const [selectedFreeProducts, setSelectedFreeProducts] = useState([]);
  const [selectedCategories, setSelectedCategories] = useState([]);
  const [errors, setErrors] = useState({});

  const createRule = useCreateRule();
  const { success, error } = useNotification();

  const validate = () => {
    const errs = {};
    if (!formData.title.trim()) errs.title = 'Rule name is required';
    if (step >= 2 && formData.apply_to === 'specific_products' && selectedBuyProducts.length === 0) {
      errs.buy_products = 'Select at least one trigger product';
    }
    if (step >= 2 && formData.apply_to === 'specific_categories' && selectedCategories.length === 0) {
      errs.categories = 'Select at least one category';
    }
    if (step >= 2 && (formData.rule_type === 'buy_x_get_y' || formData.rule_type === 'buy_cat_get_free') && selectedFreeProducts.length === 0) {
      errs.free_products = 'Select at least one gift product';
    }
    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const goNext = () => {
    if (!validate()) return;
    setStep((s) => Math.min(4, s + 1));
  };

  const goPrev = () => setStep((s) => Math.max(1, s - 1));

  const submitRule = async (status) => {
    if (!validate()) return;
    const data = {
      ...formData,
      status,
      buy_product_ids: selectedBuyProducts.map((p) => p.id),
      free_product_ids: selectedFreeProducts.map((p) => p.id),
      category_ids: selectedCategories.map((c) => c.id),
      max_free_qty: formData.max_free_qty ? Number(formData.max_free_qty) : null,
      start_date: formData.start_date || null,
      end_date: formData.end_date || null,
    };
    try {
      await createRule.mutateAsync(data);
      success('Rule created successfully');
      window.location.href = 'admin.php?page=buy-one-get-one&tab=rules';
    } catch (err) {
      error(err.message || 'Failed to create rule');
    }
  };

  const stepContent = () => {
    switch (step) {
      case 1: return <Step1 formData={formData} setFormData={setFormData} />;
      case 2: return <Step2 formData={formData} setFormData={setFormData} selectedBuyProducts={selectedBuyProducts} setSelectedBuyProducts={setSelectedBuyProducts} selectedFreeProducts={selectedFreeProducts} setSelectedFreeProducts={setSelectedFreeProducts} selectedCategories={selectedCategories} setSelectedCategories={setSelectedCategories} errors={errors} />;
      case 3: return <Step3 formData={formData} setFormData={setFormData} />;
      case 4: return <Step4 formData={formData} selectedBuyProducts={selectedBuyProducts} selectedFreeProducts={selectedFreeProducts} onLaunch={() => submitRule('active')} onDraft={() => submitRule('inactive')} isLoading={createRule.isPending} />;
      default: return null;
    }
  };

  return (
    <AppShell
      crumb={['Bogo', 'BOGO Rules', 'New rule']}
      actions={
        <>
          <button className="bogo-button bogo-button--sm bogo-button--ghost" onClick={() => submitRule('inactive')} disabled={createRule.isPending}>
            Save draft
          </button>
          {step < 4 ? (
            <button className="bogo-button bogo-button--primary bogo-button--sm" onClick={goNext}>
              Continue <ArrowIcon size={13} />
            </button>
          ) : null}
        </>
      }
    >
      <div className="bogo-row" style={{ alignItems: 'flex-start', justifyContent: 'space-between', marginBottom: 22 }}>
        <div>
          <div className="bogo-page-header__title">Create BOGO rule</div>
          <div className="bogo-page-header__desc">Set up a buy-one-get-one offer in 4 steps. You can edit anything later.</div>
        </div>
        <Stepper active={step} />
      </div>

      {errors.title && step === 1 && (
        <div style={{ marginBottom: 14, padding: '10px 14px', background: '#fff0f0', border: '1px solid #ffc0c0', borderRadius: 10, color: 'var(--danger-clr)', fontSize: 13 }}>
          {errors.title}
        </div>
      )}

      <div className="bogo-wizard">
        <div className="bogo-col" style={{ gap: 16 }}>
          {stepContent()}

          {step < 4 && (
            <div className="bogo-row" style={{ justifyContent: 'space-between', marginTop: 4 }}>
              {step > 1 ? (
                <button className="bogo-button bogo-button--sm" onClick={goPrev}>← Back</button>
              ) : (
                <button className="bogo-button bogo-button--sm bogo-button--ghost" onClick={() => window.history.back()}>Cancel</button>
              )}
              <button className="bogo-button bogo-button--primary bogo-button--sm" onClick={goNext}>
                Continue <ArrowIcon size={13} />
              </button>
            </div>
          )}
        </div>

        <div className="bogo-col" style={{ gap: 16 }}>
          <CartPreview formData={formData} selectedBuyProducts={selectedBuyProducts} selectedFreeProducts={selectedFreeProducts} step={step} />
          <SummaryRail formData={formData} step={step} selectedBuyProducts={selectedBuyProducts} selectedFreeProducts={selectedFreeProducts} />
        </div>
      </div>
    </AppShell>
  );
}

export default CreateRulePage;
