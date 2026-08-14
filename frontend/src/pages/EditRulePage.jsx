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
      success(__('Rule updated successfully', 'bogofy'));
      window.location.href = 'admin.php?page=bogofy&tab=rules';
    } catch (err) {
      error(err.message || __('Failed to update rule', 'bogofy'));
    }
  };

  if (isLoading) {
    return (
      <AppShell crumb={['Bogofy', __('Bogofy Rules', 'bogofy'), __('Edit rule', 'bogofy')]}>
        <PageLoader />
      </AppShell>
    );
  }

  if (fetchError || !rule) {
    return (
      <AppShell crumb={['Bogofy', __('Bogofy Rules', 'bogofy'), __('Edit rule', 'bogofy')]}>
        <div style={{ textAlign: 'center', padding: '48px 20px', color: 'var(--muted)' }}>
          <div style={{ fontSize: 15, fontWeight: 600, color: 'var(--ink)', marginBottom: 8 }}>{__('Failed to load rule', 'bogofy')}</div>
          <button
            onClick={() => window.history.back()}
            className="bogo-button bogo-button--sm"
          >
            {__('Go back', 'bogofy')}
          </button>
        </div>
      </AppShell>
    );
  }

  return (
    <AppShell
      crumb={['Bogofy', __('Bogofy Rules', 'bogofy'), rule.title || __('Edit rule', 'bogofy')]}
      actions={
        <>
          <button className="bogo-button bogo-button--sm bogo-button--ghost" onClick={() => window.history.back()}>
            {__('Cancel', 'bogofy')}
          </button>
          <button
            className="bogo-button bogo-button--primary bogo-button--sm"
            form="rule-edit-form"
            type="submit"
            disabled={updateRule.isPending}
          >
            <CheckIcon size={14} />
            {updateRule.isPending ? __('Saving…', 'bogofy') : __('Save changes', 'bogofy')}
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
