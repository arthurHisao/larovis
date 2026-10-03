import fsdPlugin from "@conarti/eslint-plugin-feature-sliced";
import pluginVue from "eslint-plugin-vue";
import vueParser from "vue-eslint-parser";

export default [
  // 1. Aplica a base recomendada do Vue para ele entender arquivos .vue
  ...pluginVue.configs["flat/recommended"],

  // 2. Aplica as regras recomendadas do FSD
  fsdPlugin({
    alias: "@",
  }),

  // 3. Une os dois motores para trabalharem juntos
  {
    files: ["src/**/*.vue", "src/**/*.js", "src/**/*.ts"],
    languageOptions: {
      parser: vueParser, // Obriga o ESLint a usar o interpretador do Vue
      parserOptions: {
        sourceType: "module",
      },
    },
  },
];
