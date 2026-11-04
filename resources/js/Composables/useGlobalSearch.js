import { ref } from 'vue';

const globalSearchQuery = ref('');

export function useGlobalSearch() {
  const setGlobalSearch = (val) => {
    globalSearchQuery.value = val || '';
  };

  const clearGlobalSearch = () => {
    globalSearchQuery.value = '';
  };

  return {
    globalSearchQuery,
    setGlobalSearch,
    clearGlobalSearch,
  };
}
