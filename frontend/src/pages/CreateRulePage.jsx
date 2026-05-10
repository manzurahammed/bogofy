import React from 'react';
import { useCreateRule } from '../hooks/useRules';
import { useNotification } from '../hooks/useNotification';
import { RuleForm } from '../components/Rules/RuleForm';

function CreateRulePage() {
  const createRule = useCreateRule();
  const { success, error } = useNotification();

  const handleSubmit = async (data) => {
    try {
      await createRule.mutateAsync(data);
      success('Rule created successfully');
      window.location.href = 'admin.php?page=buy-one-get-one&tab=rules';
    } catch (err) {
      error(err.message || 'Failed to create rule');
    }
  };

  return (
    <div className="bogo-max-w-4xl">
      <RuleForm onSubmit={handleSubmit} isLoading={createRule.isPending} />
    </div>
  );
}

export default CreateRulePage;
