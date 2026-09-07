import React from "react";
import { __ } from "@wordpress/i18n";
import { CheckIcon } from "../../Icons";

function Stepper({ active, onStepClick }) {
  const steps = [
    __("Offer type", "bogofy"),
    __("Products", "bogofy"),
    __("Options", "bogofy"),
    __("Review", "bogofy"),
  ];
  return (
    <div className="bogo-stepper">
      {steps.map((s, i) => {
        const n = i + 1;
        const isDone = n < active;
        const isActive = n === active;
        const state = isDone
          ? "bogo-stepper__step--done"
          : isActive
            ? "bogo-stepper__step--active"
            : "";
        // Completed steps can be clicked to jump back to them.
        const clickable = isDone && typeof onStepClick === "function";
        return (
          <React.Fragment key={i}>
            <button
              type="button"
              className={`bogo-stepper__step ${state}${
                clickable ? " bogo-stepper__step--clickable" : ""
              }`}
              onClick={clickable ? () => onStepClick(n) : undefined}
              disabled={!clickable}
              aria-current={isActive ? "step" : undefined}
            >
              <span className="bogo-stepper__num">
                {isDone ? <CheckIcon size={12} sw={2.5} /> : n}
              </span>
              <span>{s}</span>
            </button>
            {i < steps.length - 1 && <div className="bogo-stepper__sep" />}
          </React.Fragment>
        );
      })}
    </div>
  );
}

export default Stepper;
