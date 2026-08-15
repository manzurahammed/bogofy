import '@testing-library/jest-dom';

// Mock window.bogoAdmin
window.bogoAdmin = {
  apiUrl: 'http://localhost/wp-json/bogofy/v1',
  nonce: 'test-nonce',
  adminUrl: 'http://localhost/wp-admin/',
  pluginUrl: 'http://localhost/wp-content/plugins/bogofy/',
};

// Mock window.location
delete window.location;
window.location = {
  href: 'http://localhost/wp-admin/admin.php?page=bogofy',
  search: '?page=bogofy',
  assign: jest.fn(),
};

// Mock matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: jest.fn().mockImplementation((query) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: jest.fn(),
    removeListener: jest.fn(),
    addEventListener: jest.fn(),
    removeEventListener: jest.fn(),
    dispatchEvent: jest.fn(),
  })),
});
