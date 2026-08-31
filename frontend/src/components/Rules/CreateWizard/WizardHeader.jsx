import React from "react";
import { __ } from "@wordpress/i18n";
import Stepper from "./Stepper";

function WizardHeader({ step }) {
  return (
    <div
      className="bogo-row"
      style={{
        alignItems: "flex-start",
        justifyContent: "space-between",
        marginBottom: 22,
      }}
    >
      <div>
        <div className="bogo-page-header__title">
          {__("Create Bogofy rule", "bogofy")}
        </div>
        <div className="bogo-page-header__desc">
          {__(
            "Set up a bogofy offer in 3 steps. You can edit anything later.",
            "bogofy",
          )}
        </div>
      </div>
      <Stepper active={step} />
    </div>
  );
}

export default WizardHeader;
