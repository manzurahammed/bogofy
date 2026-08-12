import React from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { useRule, useUpdateRule } from '../hooks/useRules';
import { useNotification } from '../hooks/useNotification';
import { RuleForm } from '../components/Rules/RuleForm';
import { PageLoader } from '../components/Shared/Loader';
import AppShell from '../components/Layout/AppShell';
import { CheckIcon } from '../components/Icons';

function EditRulePage({ ruleId }) {
  const { data: rule, isLoading, error: fetchError } = useRule(ruleId);
  const updateRule = useUpdateRule();
  const { success, error } = useNotification();

  const handleSubmit = async (data) => {
    try {
      await updateRule.mutateAsync({ id: ruleId, data });
      success(__('Rule updated successfully', 'buy-one-get-one'));
      window.location.href = 'admin.php?page=buy-one-get-one&tab=rules';
    } catch (err) {
      error(err.message || __('Failed to update rule', 'buy-one-get-one'));
    }
  };

  if (isLoading) {
    return (
      <AppShell crumb={['Bogo', __('BOGO Rules', 'buy-one-get-one'), __('Edit rule', 'buy-one-get-one')]}>
        <PageLoader />
      </AppShell>
    );
  }

  if (fetchError || !rule) {
    return (
      <AppShell crumb={['Bogo', __('BOGO Rules', 'buy-one-get-one'), __('Edit rule', 'buy-one-get-one')]}>
        <div style={{ textAlign: 'center', padding: '48px 20px', color: 'var(--muted)' }}>
          <div style={{ fontSize: 15, fontWeight: 600, color: 'var(--ink)', marginBottom: 8 }}>{__('Failed to load rule', 'buy-one-get-one')}</div>
          <button
            onClick={() => window.history.back()}
            className="bogo-button bogo-button--sm"
          >
            {__('Go back', 'buy-one-get-one')}
          </button>
        </div>
      </AppShell>
    );
  }

  return (
    <AppShell
      crumb={['Bogo', __('BOGO Rules', 'buy-one-get-one'), rule.title || __('Edit rule', 'buy-one-get-one')]}
      actions={
        <>
          <button className="bogo-button bogo-button--sm bogo-button--ghost" onClick={() => window.history.back()}>
            {__('Cancel', 'buy-one-get-one')}
          </button>
          <button
            className="bogo-button bogo-button--primary bogo-button--sm"
            form="rule-edit-form"
            type="submit"
            disabled={updateRule.isPending}
          >
            <CheckIcon size={14} />
            {updateRule.isPending ? __('Saving…', 'buy-one-get-one') : __('Save changes', 'buy-one-get-one')}
          </button>
        </>
      }
    >
      <div style={{ maxWidth: 860 }}>
        <RuleForm
          formId="rule-edit-form"
          initialData={rule}
          onSubmit={handleSubmit}
          isLoading={updateRule.isPending}
        />
      </div>
    </AppShell>
  );
}

EditRulePage.propTypes = {
  ruleId: PropTypes.number.isRequired,
};

export default EditRulePage;
