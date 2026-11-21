import React from "react";
import CartPreview from "./CartPreview";
import SummaryRail from "./SummaryRail";

function WizardSidebar({
  formData,
  step,
  selectedBuyProducts,
  selectedFreeProducts,
  selectedExcludedProducts,
  selectedExcludedCategories,
}) {
  return (
    <div className="bogo-col" style={{ gap: 16 }}>
      <CartPreview
        formData={formData}
        selectedBuyProducts={selectedBuyProducts}
        selectedFreeProducts={selectedFreeProducts}
        step={step}
      />
      <SummaryRail
        formData={formData}
        step={step}
        selectedBuyProducts={selectedBuyProducts}
        selectedFreeProducts={selectedFreeProducts}
        selectedExcludedProducts={selectedExcludedProducts}
        selectedExcludedCategories={selectedExcludedCategories}
      />
    </div>
  );
}

export default WizardSidebar;
