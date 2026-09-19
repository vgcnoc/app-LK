import re

with open('resources/js/Pages/Billing.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add applied* refs
content = content.replace("const endDateFilter = ref('');\n", "const endDateFilter = ref('');\n\nconst appliedSearchQuery = ref('');\nconst appliedStatusFilter = ref('all');\nconst appliedAreaFilter = ref('all');\nconst appliedStartDateFilter = ref('');\nconst appliedEndDateFilter = ref('');\n\nconst applyFilters = () => {\n    appliedSearchQuery.value = searchQuery.value;\n    appliedStatusFilter.value = statusFilter.value;\n    appliedAreaFilter.value = areaFilter.value;\n    appliedStartDateFilter.value = startDateFilter.value;\n    appliedEndDateFilter.value = endDateFilter.value;\n    currentPage.value = 1;\n};\n")

# 2. Update filteredCustomers
content = content.replace("const query = searchQuery.value.trim().toLowerCase();", "const query = appliedSearchQuery.value.trim().toLowerCase();")
content = content.replace("statusFilter.value ===", "appliedStatusFilter.value ===")
content = content.replace("areaFilter.value ===", "appliedAreaFilter.value ===")
content = content.replace("startDateFilter.value", "appliedStartDateFilter.value")
content = content.replace("endDateFilter.value", "appliedEndDateFilter.value")

# 3. Update watch
content = content.replace("watch([searchQuery, statusFilter, areaFilter, activeTab, startDateFilter, endDateFilter], () => {", "watch([appliedSearchQuery, appliedStatusFilter, appliedAreaFilter, activeTab, appliedStartDateFilter, appliedEndDateFilter], () => {")

# 4. Update resetFilters
content = content.replace("searchQuery.value = '';\n    statusFilter.value = 'all';\n    areaFilter.value = 'all';\n    startDateFilter.value = '';\n    endDateFilter.value = '';", "searchQuery.value = '';\n    statusFilter.value = 'all';\n    areaFilter.value = 'all';\n    startDateFilter.value = '';\n    endDateFilter.value = '';\n    applyFilters();")

# 5. Make sure the area filter always shows (remove v-if)
content = content.replace('v-if=\"uniqueAreas.length > 0\"\\n                                    v-model=\"areaFilter\"', 'v-model=\"areaFilter\"')

# 6. Add "Terapkan" button next to "Reset Filters"
reset_btn = \"\"\"                                    <button
                                        v-if=\"searchQuery || statusFilter !== 'all' || areaFilter !== 'all' || startDateFilter || endDateFilter\"
                                        @click=\"resetFilters\"
                                        type=\"button\"
                                        class=\"inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 shadow-sm transition hover:bg-slate-50\"
                                    >\"\"\"
apply_btn = \"\"\"                                    <button
                                        @click=\"applyFilters\"
                                        type=\"button\"
                                        class=\"inline-flex items-center gap-1 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-medium text-white shadow-sm transition hover:bg-indigo-700\"
                                    >
                                        <svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-4 w-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"2\">
                                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M5 13l4 4L19 7\" />
                                        </svg>
                                        Terapkan
                                    </button>\"\"\"
content = content.replace(reset_btn, apply_btn + \"\\n\" + reset_btn)

with open('resources/js/Pages/Billing.vue', 'w', encoding='utf-8') as f:
    f.write(content)
print("Done")
