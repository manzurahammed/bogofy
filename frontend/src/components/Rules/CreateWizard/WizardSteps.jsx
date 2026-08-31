import React from "react";
import StepOfferType from "./StepOfferType";
import StepProducts from "./StepProducts";
import StepReview from "./StepReview";

/**
 * Renders the active wizard step based on the wizard state.
 *
 * @param {Object} props
 * @param {Object} props.wizard - The bag returned by useCreateRuleWizard.
 */
function WizardSteps({ wizard }) {
  const {
    step,
    formData,
    setFormData,
    selectedBuyProducts,
    setSelectedBuyProducts,
    selectedFreeProducts,
    setSelectedFreeProducts,
    errors,
    submitRule,
    isPending,
  } = wizard;

  switch (step) {
    case 1:
      return <StepOfferType formData={formData} setFormData={setFormData} />;
    case 2:
      return (
        <StepProducts
          formData={formData}
          setFormData={setFormData}
          selectedBuyProducts={selectedBuyProducts}
          setSelectedBuyProducts={setSelectedBuyProducts}
          selectedFreeProducts={selectedFreeProducts}
          setSelectedFreeProducts={setSelectedFreeProducts}
          errors={errors}
        />
      );
    case 3:
      return (
        <StepReview
          formData={formData}
          selectedBuyProducts={selectedBuyProducts}
          selectedFreeProducts={selectedFreeProducts}
          onLaunch={() => submitRule("active")}
          onDraft={() => submitRule("inactive")}
          isLoading={isPending}
        />
      );
    default:
      return null;
  }
}

export default WizardSteps;
