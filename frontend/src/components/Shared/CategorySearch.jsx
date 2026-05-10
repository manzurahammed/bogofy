import React, { useState, useRef, useEffect } from 'react';
import PropTypes from 'prop-types';
import { useCategorySearch } from '../../hooks/useProducts';
import { Loader } from './Loader';

export function CategorySearch({ selectedCategories = [], onChange, placeholder = 'Search categories...' }) {
  const [isOpen, setIsOpen] = useState(false);
  const [inputValue, setInputValue] = useState('');
  const containerRef = useRef(null);

  const { categories, isLoading, search } = useCategorySearch();

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (containerRef.current && !containerRef.current.contains(e.target)) {
        setIsOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleInputChange = (e) => {
    const value = e.target.value;
    setInputValue(value);
    search(value);
    setIsOpen(true);
  };

  const handleSelectCategory = (category) => {
    if (!selectedCategories.find((c) => c.id === category.id)) {
      onChange([...selectedCategories, category]);
    }
    setInputValue('');
    setIsOpen(false);
  };

  const handleRemoveCategory = (categoryId) => {
    onChange(selectedCategories.filter((c) => c.id !== categoryId));
  };

  const filteredCategories = categories.filter(
    (category) => !selectedCategories.find((c) => c.id === category.id)
  );

  return (
    <div ref={containerRef} className="bogo-relative">
      {/* Selected categories */}
      {selectedCategories.length > 0 && (
        <div className="bogo-flex bogo-flex-wrap bogo-gap-2 bogo-mb-2">
          {selectedCategories.map((category) => (
            <span
              key={category.id}
              className="bogo-inline-flex bogo-items-center bogo-gap-1 bogo-px-2 bogo-py-1 bogo-text-sm bogo-bg-primary-50 bogo-text-primary-700 bogo-rounded-md"
            >
              {category.name}
              <button
                type="button"
                onClick={() => handleRemoveCategory(category.id)}
                className="bogo-text-primary-500 hover:bogo-text-primary-700"
              >
                <svg className="bogo-w-4 bogo-h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                </svg>
              </button>
            </span>
          ))}
        </div>
      )}

      {/* Search input */}
      <div className="bogo-relative">
        <input
          type="text"
          value={inputValue}
          onChange={handleInputChange}
          onFocus={() => {
            setIsOpen(true);
            if (!inputValue) search('');
          }}
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
      {isOpen && (
        <div className="bogo-absolute bogo-z-10 bogo-w-full bogo-mt-1 bogo-bg-white bogo-border bogo-border-gray-200 bogo-rounded-md bogo-shadow-lg bogo-max-h-60 bogo-overflow-auto">
          {filteredCategories.length === 0 ? (
            <div className="bogo-px-4 bogo-py-3 bogo-text-sm bogo-text-gray-500">
              {isLoading ? 'Loading...' : 'No categories found'}
            </div>
          ) : (
            <ul className="bogo-py-1">
              {filteredCategories.map((category) => (
                <li key={category.id}>
                  <button
                    type="button"
                    onClick={() => handleSelectCategory(category)}
                    className="bogo-w-full bogo-flex bogo-items-center bogo-justify-between bogo-px-4 bogo-py-2 bogo-text-sm bogo-text-left hover:bogo-bg-gray-50"
                  >
                    <span className="bogo-text-gray-900">{category.name}</span>
                    <span className="bogo-text-xs bogo-text-gray-500">
                      {category.count} products
                    </span>
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

CategorySearch.propTypes = {
  selectedCategories: PropTypes.arrayOf(
    PropTypes.shape({
      id: PropTypes.number.isRequired,
      name: PropTypes.string.isRequired,
    })
  ),
  onChange: PropTypes.func.isRequired,
  placeholder: PropTypes.string,
};

export default CategorySearch;
