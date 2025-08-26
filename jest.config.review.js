module.exports = {
  preset: '@vue/cli-plugin-unit-jest/presets/typescript-and-babel',
  testEnvironment: 'jsdom',
  rootDir: '.',
  
  // Test files patterns
  testMatch: [
    '**/tests/unit/entities/review/**/*.spec.ts',
    '**/tests/unit/features/review-management/**/*.spec.ts',
    '**/tests/unit/widgets/profile-dashboard/tabs/ReviewsTab.spec.ts',
    '**/resources/js/src/entities/review/**/*.test.ts',
    '**/resources/js/src/features/review-management/**/*.test.ts'
  ],
  
  // Module resolution
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/resources/js/src/$1',
    '^~/(.*)$': '<rootDir>/resources/js/src/$1',
    '\\.(css|less|scss|sass)$': 'identity-obj-proxy',
    '\\.(jpg|jpeg|png|gif|svg)$': '<rootDir>/tests/unit/__mocks__/fileMock.js'
  },
  
  // Transform files
  transform: {
    '^.+\\.vue$': '@vue/vue3-jest',
    '^.+\\.(ts|tsx)$': ['ts-jest', {
      tsconfig: {
        jsx: 'preserve',
        esModuleInterop: true,
        allowSyntheticDefaultImports: true
      }
    }],
    '^.+\\.js$': 'babel-jest'
  },
  
  // Coverage configuration
  collectCoverage: true,
  collectCoverageFrom: [
    'resources/js/src/entities/review/**/*.{ts,vue}',
    'resources/js/src/features/review-management/**/*.{ts,vue}',
    'resources/js/src/widgets/profile-dashboard/tabs/ReviewsTab.vue',
    '!**/*.d.ts',
    '!**/node_modules/**',
    '!**/*.test.ts',
    '!**/*.spec.ts',
    '!**/tests/**'
  ],
  
  coverageDirectory: '<rootDir>/coverage/frontend',
  coverageReporters: ['text', 'lcov', 'html', 'json'],
  
  // Coverage thresholds
  coverageThreshold: {
    global: {
      branches: 70,
      functions: 70,
      lines: 70,
      statements: 70
    }
  },
  
  // Setup files
  setupFilesAfterEnv: [
    '<rootDir>/tests/unit/setup.ts'
  ],
  
  // Test environment options
  testEnvironmentOptions: {
    customExportConditions: ['node', 'node-addons']
  },
  
  // Global test utilities
  globals: {
    'ts-jest': {
      tsconfig: '<rootDir>/tsconfig.json'
    }
  },
  
  // Test timeout
  testTimeout: 10000,
  
  // Verbose output
  verbose: true,
  
  // Clear mocks between tests
  clearMocks: true,
  
  // Restore mocks after each test
  restoreMocks: true
};