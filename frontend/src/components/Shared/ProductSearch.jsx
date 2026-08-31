import React, { useState, useRef, useEffect } from "react";
import PropTypes from "prop-types";
import { __ } from "@wordpress/i18n";
import { useProductSearch } from "../../hooks/useProducts";
import { Loader } from "./Loader";

export function ProductSearch({
  selectedProducts = [],
  onChange,
  placeholder = __("Search products...", "bogofy"),
}) {
  const [isOpen, setIsOpen] = useState(false);
  const [inputValue, setInputValue] = useState("");
  const containerRef = useRef(null);
  const inputRef = useRef(null);

  const { products, isLoading, search } = useProductSearch({ minChars: 2 });

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (containerRef.current && !containerRef.current.contains(e.target)) {
        setIsOpen(false);
      }
    };

    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const handleInputChange = (e) => {
    const value = e.target.value;
    setInputValue(value);
    search(value);
    setIsOpen(true);
  };

  const handleSelectProduct = (product) => {
    if (!selectedProducts.find((p) => p.id === product.id)) {
      onChange([...selectedProducts, product]);
    }
    setInputValue("");
    setIsOpen(false);
  };

  const handleRemoveProduct = (productId) => {
    onChange(selectedProducts.filter((p) => p.id !== productId));
  };

  const filteredProducts = products.filter(
    (product) => !selectedProducts.find((p) => p.id === product.id),
  );

  return (
    <div ref={containerRef} className="bogo-relative">
      {/* Selected products */}
      {selectedProducts.length > 0 && (
        <div className="bogo-flex bogo-flex-wrap bogo-gap-2 bogo-mb-2">
          {selectedProducts.map((product) => (
            <span
              key={product.id}
              className="bogo-inline-flex bogo-items-center bogo-gap-1 bogo-px-2 bogo-py-1 bogo-text-sm bogo-bg-primary-50 bogo-text-primary-700 bogo-rounded-md"
            >
              {product.name}
              <button
                type="button"
                onClick={() => handleRemoveProduct(product.id)}
                className="bogo-text-primary-500 hover:bogo-text-primary-700"
              >
                <svg
                  className="bogo-w-4 bogo-h-4"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fillRule="evenodd"
                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                    clipRule="evenodd"
                  />
                </svg>
              </button>
            </span>
          ))}
        </div>
      )}

      {/* Search input */}
      <div className="bogo-relative">
        <input
          ref={inputRef}
          type="text"
          value={inputValue}
          onChange={handleInputChange}
          onFocus={() => setIsOpen(true)}
          placeholder={placeholder}
          className="bogo-input bogo-pr-10"
        />
        {isLoading && (
          <div className="bogo-absolute bogo-right-3 bogo-top-1/2 bogo-transform bogo--translate-y-1/2">
            <Loader size="sm" />
          </div>
        )}
      </div>

      {/* Dropdown */}
      {isOpen && inputValue.length >= 2 && (
        <div className="bogo-absolute bogo-z-10 bogo-w-full bogo-mt-1 bogo-bg-white bogo-border bogo-border-gray-200 bogo-rounded-md bogo-shadow-lg bogo-max-h-60 bogo-overflow-auto">
          {filteredProducts.length === 0 ? (
            <div className="bogo-px-4 bogo-py-3 bogo-text-sm bogo-text-gray-500">
              {isLoading
                ? __("Searching...", "bogofy")
                : __("No products found", "bogofy")}
            </div>
          ) : (
            <ul className="bogo-py-1">
              {filteredProducts.map((product) => (
                <li key={product.id}>
                  <button
                    type="button"
                    onClick={() => handleSelectProduct(product)}
                    className="bogo-w-full bogo-flex bogo-items-center bogo-gap-3 bogo-px-4 bogo-py-2 bogo-text-sm bogo-text-left hover:bogo-bg-gray-50"
                  >
                    {product.image && (
                      <img
                        src={product.image}
                        alt={product.name}
                        className="bogo-w-10 bogo-h-10 bogo-object-cover bogo-rounded"
                      />
                    )}
                    <div>
                      <div className="bogo-font-medium bogo-text-gray-900">
                        {product.name}
                      </div>
                      {product.sku && (
                        <div className="bogo-text-xs bogo-text-gray-500">
                          SKU: {product.sku}
                        </div>
                      )}
                    </div>
                  </button>
                </li>
              ))}
            </ul>
          )}
        </div>
      )}
    </div>
  );
}

ProductSearch.propTypes = {
  selectedProducts: PropTypes.arrayOf(
    PropTypes.shape({
      id: PropTypes.number.isRequired,
      name: PropTypes.string.isRequired,
    }),
  ),
  onChange: PropTypes.func.isRequired,
  placeholder: PropTypes.string,
};

export default ProductSearch;
