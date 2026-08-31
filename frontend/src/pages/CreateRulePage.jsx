import React from "react";
import { __ } from "@wordpress/i18n";
import AppShell from "../components/Layout/AppShell";
import { useCreateRuleWizard } from "../components/Rules/CreateWizard/useCreateRuleWizard";
import WizardHeader from "../components/Rules/CreateWizard/WizardHeader";
import WizardActions from "../components/Rules/CreateWizard/WizardActions";
import WizardSteps from "../components/Rules/CreateWizard/WizardSteps";
import WizardNav from "../components/Rules/CreateWizard/WizardNav";
import WizardSidebar from "../components/Rules/CreateWizard/WizardSidebar";

function CreateRulePage() {
  const wizard = useCreateRuleWizard();
  const {
    step,
    formData,
    selectedBuyProducts,
    selectedFreeProducts,
    errors,
    goNext,
    goPrev,
    submitRule,
    isPending,
  } = wizard;

  return (
    <AppShell
      crumb={["Bogofy", __("Bogofy Rules", "bogofy"), __("New rule", "bogofy")]}
      actions={
        <WizardActions
          step={step}
          onSaveDraft={() => submitRule("inactive")}
          onNext={goNext}
          isPending={isPending}
        />
      }
    >
      <WizardHeader step={step} />

      {errors.title && step === 1 && (
        <div
          style={{
            marginBottom: 14,
            padding: "10px 14px",
            background: "#fff0f0",
            border: "1px solid #ffc0c0",
            borderRadius: 10,
            color: "var(--danger-clr)",
            fontSize: 13,
          }}
        >
          {errors.title}
        </div>
      )}

      <div className="bogo-wizard">
        <div className="bogo-col" style={{ gap: 16 }}>
          <WizardSteps wizard={wizard} />
          {step < 3 && (
            <WizardNav step={step} onPrev={goPrev} onNext={goNext} />
          )}
        </div>

        <WizardSidebar
          formData={formData}
          step={step}
          selectedBuyProducts={selectedBuyProducts}
          selectedFreeProducts={selectedFreeProducts}
        />
      </div>
    </AppShell>
  );
}

export default CreateRulePage;
