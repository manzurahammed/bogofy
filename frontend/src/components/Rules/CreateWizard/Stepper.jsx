import React from "react";
import { __ } from "@wordpress/i18n";
import { CheckIcon } from "../../Icons";

function Stepper({ active }) {
  const steps = [
    __("Offer type", "bogofy"),
    __("Products", "bogofy"),
    __("Review", "bogofy"),
  ];
  return (
    <div className="bogo-stepper">
      {steps.map((s, i) => {
        const n = i + 1;
        const state =
          n < active
            ? "bogo-stepper__step--done"
            : n === active
              ? "bogo-stepper__step--active"
              : "";
        return (
          <React.Fragment key={i}>
            <div className={`bogo-stepper__step ${state}`}>
              <span className="bogo-stepper__num">
                {n < active ? <CheckIcon size={12} sw={2.5} /> : n}
              </span>
              <span>{s}</span>
            </div>
            {i < steps.length - 1 && <div className="bogo-stepper__sep" />}
          </React.Fragment>
        );
      })}
    </div>
  );
}

export default Stepper;
