import React from "react";
import CartPreview from "./CartPreview";
import SummaryRail from "./SummaryRail";

function WizardSidebar({
  formData,
  step,
  selectedBuyProducts,
  selectedFreeProducts,
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
      />
    </div>
  );
}

export default WizardSidebar;
