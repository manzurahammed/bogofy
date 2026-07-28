import React from 'react';
import PropTypes from 'prop-types';
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
      success('Rule updated successfully');
      window.location.href = 'admin.php?page=buy-one-get-one&tab=rules';
    } catch (err) {
      error(err.message || 'Failed to update rule');
    }
  };

  if (isLoading) {
    return (
      <AppShell crumb={['Bogo', 'BOGO Rules', 'Edit rule']}>
        <PageLoader />
      </AppShell>
    );
  }

  if (fetchError || !rule) {
    return (
      <AppShell crumb={['Bogo', 'BOGO Rules', 'Edit rule']}>
        <div style={{ textAlign: 'center', padding: '48px 20px', color: 'var(--muted)' }}>
          <div style={{ fontSize: 15, fontWeight: 600, color: 'var(--ink)', marginBottom: 8 }}>Failed to load rule</div>
          <button
            onClick={() => window.history.back()}
            className="bogo-button bogo-button--sm"
          >
            Go back
          </button>
        </div>
      </AppShell>
    );
  }

  return (
    <AppShell
      crumb={['Bogo', 'BOGO Rules', rule.title || 'Edit rule']}
      actions={
        <>
          <button className="bogo-button bogo-button--sm bogo-button--ghost" onClick={() => window.history.back()}>
            Cancel
          </button>
          <button
            className="bogo-button bogo-button--primary bogo-button--sm"
            form="rule-edit-form"
            type="submit"
            disabled={updateRule.isPending}
          >
            <CheckIcon size={14} />
            {updateRule.isPending ? 'Saving…' : 'Save changes'}
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
