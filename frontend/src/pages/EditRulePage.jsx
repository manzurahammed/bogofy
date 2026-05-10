import React from 'react';
import PropTypes from 'prop-types';
import { useRule, useUpdateRule } from '../hooks/useRules';
import { useNotification } from '../hooks/useNotification';
import { RuleForm } from '../components/Rules/RuleForm';
import { PageLoader } from '../components/Shared/Loader';

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
    return <PageLoader />;
  }

  if (fetchError || !rule) {
    return (
      <div className="bogo-text-center bogo-py-12">
        <p className="bogo-text-danger-500">Failed to load rule. Please try again.</p>
        <button
          onClick={() => window.history.back()}
          className="bogo-mt-4 bogo-btn bogo-btn-secondary"
        >
          Go Back
        </button>
      </div>
    );
  }

  return (
    <div className="bogo-max-w-4xl">
      <RuleForm
        initialData={rule}
        onSubmit={handleSubmit}
        isLoading={updateRule.isPending}
      />
    </div>
  );
}

EditRulePage.propTypes = {
  ruleId: PropTypes.number.isRequired,
};

export default EditRulePage;
