module.exports = {
    "env": {
        "browser": true,
        "es6": true
    },
    "extends": ["eslint:recommended", "plugin:import/errors"],
    "globals": {
        "Atomics": "readonly",
        "SharedArrayBuffer": "readonly"
    },
    "parserOptions": {
        "ecmaVersion": 2019,
        "sourceType": "module"
    },
    plugins: [
        "svelte3",
        "import"
    ],
    overrides: [
        {
            files: ['*.svelte'],
            processor: 'svelte3/svelte3'

        }
    ],
    "rules": {
        "indent": [
            "error",
            4
        ],
        "linebreak-style": [
            "error",
            "unix"
        ],
        "quotes": [
            "error",
            "double"
        ],
        "semi": [
            "error",
            "never"
        ],
        "import/export": ["error"],
        "import/order": ["error", {"newlines-between": "always", "alphabetize": {"order": "asc", "caseInsensitive": true}}],
        "import/newline-after-import": ["error"],
        "import/no-absolute-path": ["error"]
    },
    "settings": {
        "svelte3/ignore-styles": function () { return true },
        "import/extensions": [".js"],
        "import/resolver": "webpack"
    }
}
