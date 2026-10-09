{{-- Reusable Advanced Permissions & Sidebar Access Editor Partial --}}
@props([
    'prefix' => 'create',
])

<div class="mt-5 border-t border-slate-200/90 pt-5 space-y-4"
     x-data="{
        subTab: 'actions',
        sidebarQuery: '',
        collapsedSections: {},
        toggleSectionCollapse(idx) {
            this.collapsedSections[idx] = !this.collapsedSections[idx];
        },
        isSectionCollapsed(idx) {
            return !!this.collapsedSections[idx];
        },
        hasSensitivePerms() {
            const sensitive = ['members.create', 'members.update', 'members.delete', 'smtp.manage', 'backup.manage', 'settings.manage'];
            return sensitive.some(p => this.selectedPermissions.includes(p));
        },
        financialCodes() {
            return (this.financialPermissions || []).map(p => p.code);
        },
        hasFinancialPerms() {
            return this.financialCodes().some(c => this.selectedPermissions.includes(c));
        },
        isFinancialAllSelected() {
            const codes = this.financialCodes();
            return codes.length > 0 && codes.every(c => this.selectedPermissions.includes(c));
        },
        toggleFinancial(enable) {
            const codes = this.financialCodes();
            if (enable) {
                this.selectedPermissions = Array.from(new Set([...this.selectedPermissions, ...codes]));
            } else {
                this.selectedPermissions = this.selectedPermissions.filter(c => !codes.includes(c));
            }
        },
        hasEffectivePermission(code) {
            if (this.isSuperAdminTarget === true) return true;
            if (this.permissionMode === 'custom') return this.selectedPermissions.includes(code);
            const preset = (this.rolePresets || {})[(this.selectedRole || 'staff').toUpperCase()];
            const perms = (preset && preset.permissions) ? preset.permissions : [];
            return perms.includes('*') || perms.includes(code);
        },
        effectiveFinancialCount() {
            return this.financialCodes().filter(c => this.hasEffectivePermission(c)).length;
        },
        allSidebarItems() {
            let list = [];
            (this.sidebarSections || []).forEach(sec => {
                (sec.items || []).forEach(item => {
                    list.push(item.label);
                });
            });
            return list;
        },
        filteredSidebarSections() {
            if (!this.sidebarQuery.trim()) {
                return this.sidebarSections || [];
            }
            const q = this.sidebarQuery.toLowerCase();
            return (this.sidebarSections || []).map(sec => {
                const matchedItems = (sec.items || []).filter(item => 
                    item.label.toLowerCase().includes(q) || sec.label.toLowerCase().includes(q)
                );
                return { ...sec, items: matchedItems };
            }).filter(sec => sec.items.length > 0);
        },
        totalSidebarPages() {
            return this.allSidebarItems().length;
        },
        selectedSidebarCount() {
            const all = this.allSidebarItems();
            return this.selectedSidebar.filter(x => all.includes(x)).length;
        },
        toggleAllSidebar(enable) {
            if (enable) {
                this.selectedSidebar = [...this.allSidebarItems()];
            } else {
                this.selectedSidebar = [];
            }
        },
        toggleSectionSidebar(section, enable) {
            const labels = (section.items || []).map(i => i.label);
            if (enable) {
                const set = new Set([...this.selectedSidebar, ...labels]);
                this.selectedSidebar = Array.from(set);
            } else {
                this.selectedSidebar = this.selectedSidebar.filter(l => !labels.includes(l));
            }
        },
        isSectionAllSelected(section) {
            const labels = (section.items || []).map(i => i.label);
            if (labels.length === 0) return false;
            return labels.every(l => this.selectedSidebar.includes(l));
        },
        isSectionPartialSelected(section) {
            const labels = (section.items || []).map(i => i.label);
            const count = labels.filter(l => this.selectedSidebar.includes(l)).length;
            return count > 0 && count < labels.length;
        },
        allActionCodes() {
            let codes = [];
            Object.values(this.permissionMatrix || {}).forEach(mod => {
                Object.values(mod.actions || {}).forEach(act => {
                    if (act && act.code) codes.push(act.code);
                });
            });
            return Array.from(new Set(codes));
        },
        toggleAllActions(enable) {
            if (enable) {
                const keptFinancial = this.selectedPermissions.filter(c => this.financialCodes().includes(c));
                this.selectedPermissions = Array.from(new Set([...this.allActionCodes(), ...keptFinancial]));
            } else {
                this.selectedPermissions = [];
            }
        },
        toggleModuleActions(module, enable) {
            const codes = Object.values(module.actions || {}).map(a => a.code).filter(Boolean);
            if (enable) {
                const set = new Set([...this.selectedPermissions, ...codes]);
                this.selectedPermissions = Array.from(set);
            } else {
                this.selectedPermissions = this.selectedPermissions.filter(c => !codes.includes(c));
            }
        },
        isModuleAllSelected(module) {
            const codes = Object.values(module.actions || {}).map(a => a.code).filter(Boolean);
            if (codes.length === 0) return false;
            return codes.every(c => this.selectedPermissions.includes(c));
        },
        applyRoleDefaults(roleName) {
            const r = (roleName || this.selectedRole || 'staff').toUpperCase();
            const preset = (this.rolePresets && this.rolePresets[r]) ? this.rolePresets[r] : null;
            if (preset && preset.permissions) {
                this.selectedPermissions = [...preset.permissions];
            } else {
                this.selectedPermissions = [];
            }
            this.selectedSidebar = [...this.allSidebarItems()];
        }
     }">

    {{-- Permissions Mode Selection --}}
    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Permission Configuration Model</h4>
                <p class="text-[11px] text-slate-500">Choose between inheriting preset role rights or configuring explicit custom capabilities.</p>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                  :class="permissionMode === 'custom' ? 'bg-indigo-100 text-indigo-800 ring-1 ring-indigo-300' : 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300'">
                <span x-text="permissionMode === 'custom' ? 'Option B: Custom Overrides' : 'Option A: Role Inherited'"></span>
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <!-- Option A: Role Inherited -->
            <label class="relative flex cursor-pointer rounded-lg border p-3.5 shadow-xs transition focus:outline-none"
                   :class="permissionMode === 'role' ? 'border-brand-green-500 bg-brand-green-50/40 ring-2 ring-brand-green-500/20' : 'border-slate-200 bg-white hover:border-slate-300'">
                <input type="radio" name="permission_mode" value="role" x-model="permissionMode" class="sr-only">
                <div class="flex w-full items-start gap-3">
                    <div class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border"
                         :class="permissionMode === 'role' ? 'border-brand-green-600 bg-brand-green-600 text-white' : 'border-slate-300 bg-white'">
                        <div class="h-1.5 w-1.5 rounded-full bg-white" x-show="permissionMode === 'role'"></div>
                    </div>
                    <div class="text-xs">
                        <span class="block font-bold text-slate-900">Option A: Role-Based Permissions</span>
                        <span class="mt-0.5 block text-[11px] text-slate-500">Inherit all capabilities from the selected member role. Automatically updates when role policies change.</span>
                    </div>
                </div>
            </label>

            <!-- Option B: Custom Permissions -->
            <label class="relative flex cursor-pointer rounded-lg border p-3.5 shadow-xs transition focus:outline-none"
                   :class="permissionMode === 'custom' ? 'border-indigo-500 bg-indigo-50/40 ring-2 ring-indigo-500/20' : 'border-slate-200 bg-white hover:border-slate-300'">
                <input type="radio" name="permission_mode" value="custom" x-model="permissionMode" class="sr-only">
                <div class="flex w-full items-start gap-3">
                    <div class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border"
                         :class="permissionMode === 'custom' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white'">
                        <div class="h-1.5 w-1.5 rounded-full bg-white" x-show="permissionMode === 'custom'"></div>
                    </div>
                    <div class="text-xs">
                        <span class="block font-bold text-slate-900">Option B: Custom Permissions</span>
                        <span class="mt-0.5 block text-[11px] text-slate-500">Define individual module action permissions and customize accessible sidebar sections.</span>
                    </div>
                </div>
            </label>
        </div>
    </div>

    {{-- Option A Explanatory Card (When Role-based) --}}
    <div x-show="permissionMode === 'role'" class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="flex items-start gap-3">
            <div class="rounded-lg bg-emerald-100 p-2 text-emerald-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div class="text-xs space-y-1">
                <h5 class="font-bold text-slate-900">Standard Role Inheritance Active</h5>
                <p class="text-slate-600">
                    This user will receive default permissions according to their assigned role (<span class="font-bold uppercase text-slate-800" x-text="selectedRole"></span>).
                    All navigation sections associated with this role will be visible, and backend actions will follow default team safety rules.
                </p>
                <div class="pt-2">
                    <button type="button" @click="permissionMode = 'custom'; applyRoleDefaults(selectedRole)" class="inline-flex items-center gap-1.5 text-indigo-600 hover:text-indigo-800 font-semibold underline text-xs">
                        Customize or override these permissions →
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Effective Financial Access (both modes) --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4" x-show="(financialPermissions || []).length > 0">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h5 class="text-xs font-bold uppercase tracking-wider text-slate-800">Effective Financial &amp; Cost Access</h5>
                <p class="text-[11px] text-slate-500"
                   x-text="isSuperAdminTarget === true
                        ? 'Super Administrators always have full financial access.'
                        : (permissionMode === 'custom'
                            ? 'Resolved from the custom permissions selected below.'
                            : 'Inherited from the ' + (selectedRole || 'staff') + ' role preset.')"></p>
            </div>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                  :class="effectiveFinancialCount() > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600'"
                  x-text="effectiveFinancialCount() + ' / ' + financialCodes().length + ' granted'"></span>
        </div>
        <div class="mt-2.5 flex flex-wrap gap-1.5">
            <template x-for="perm in financialPermissions" :key="'eff-' + perm.code">
                <span class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-[10px] font-semibold ring-1"
                      :class="hasEffectivePermission(perm.code) ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-slate-50 text-slate-400 ring-slate-200'"
                      :title="perm.description">
                    <span x-text="hasEffectivePermission(perm.code) ? '✓' : '✕'"></span>
                    <span x-text="perm.label"></span>
                </span>
            </template>
        </div>
    </div>

    {{-- Option B Custom Permission Editor Panel (When Custom) --}}
    <div x-show="permissionMode === 'custom'" class="space-y-4">
        {{-- Sensitive Permissions Alert Banner --}}
        <div x-show="hasSensitivePerms()" class="rounded-xl border border-amber-300 bg-amber-50/90 p-3.5 text-xs text-amber-900 flex items-start gap-2.5">
            <svg class="h-4 w-4 shrink-0 text-amber-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <strong class="font-bold">Elevated Access Alert:</strong> This configuration grants sensitive privileges (e.g. Member management, SMTP settings, or Database Backups). Verify that this team member requires administrative authority.
            </div>
        </div>
        <div x-show="hasFinancialPerms()" class="rounded-xl border border-amber-300 bg-amber-50/90 p-3.5 text-xs text-amber-900">
            <strong class="font-bold">Financial Data Access:</strong> This member will be able to see or change confidential cost information (buying price, supplier cost, or profit margin).
        </div>

        {{-- Sub-Navigation Tabs: Action Permissions vs Sidebar Access --}}
        <div class="flex items-center justify-between border-b border-slate-200">
            <nav class="flex gap-4">
                <button type="button" @click="subTab = 'actions'"
                        class="pb-2.5 text-xs font-bold border-b-2 transition flex items-center gap-2"
                        :class="subTab === 'actions' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Action Permissions
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600" x-text="selectedPermissions.length"></span>
                </button>

                <button type="button" @click="subTab = 'sidebar'"
                        class="pb-2.5 text-xs font-bold border-b-2 transition flex items-center gap-2"
                        :class="subTab === 'sidebar' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                    Sidebar Access Control
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600"
                          x-text="selectedSidebarCount() + ' / ' + totalSidebarPages()"></span>
                </button>
            </nav>

            <button type="button" @click="applyRoleDefaults(selectedRole)"
                    class="pb-2 text-[11px] font-semibold text-slate-500 hover:text-indigo-600 flex items-center gap-1 transition"
                    title="Load defaults for selected role">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Reset to Role Defaults
            </button>
        </div>

        {{-- TAB 1: Action Permissions Matrix --}}
        <div x-show="subTab === 'actions'" class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                <span class="text-xs text-slate-600">
                    Fine-tune granular actions for each module. Backend will enforce HTTP 403 on restricted actions.
                </span>
                <div class="flex items-center gap-2">
                    <button type="button" @click="toggleAllActions(true)" class="rounded px-2 py-1 text-[11px] font-semibold bg-white border border-slate-300 text-slate-700 hover:bg-slate-100">
                        Select All Actions
                    </button>
                    <button type="button" @click="toggleAllActions(false)" class="rounded px-2 py-1 text-[11px] font-semibold bg-white border border-slate-300 text-slate-700 hover:bg-slate-100">
                        Deselect All
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border border-slate-200 max-h-[380px] overflow-y-auto">
                <table class="min-w-full text-xs text-left divide-y divide-slate-200">
                    <thead class="bg-slate-100 text-slate-700 uppercase tracking-wider text-[10px] font-bold sticky top-0 z-10">
                        <tr>
                            <th class="px-3.5 py-2.5">Module</th>
                            <th class="px-2.5 py-2.5 text-center">View</th>
                            <th class="px-2.5 py-2.5 text-center">Create</th>
                            <th class="px-2.5 py-2.5 text-center">Edit</th>
                            <th class="px-2.5 py-2.5 text-center">Delete</th>
                            <th class="px-2.5 py-2.5 text-center">Export</th>
                            <th class="px-2.5 py-2.5 text-center">Import</th>
                            <th class="px-2.5 py-2.5 text-center">Approve</th>
                            <th class="px-3 py-2.5 text-right">Module All</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-for="(mod, modKey) in permissionMatrix" :key="modKey">
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-3.5 py-2.5">
                                    <div class="font-bold text-slate-800" x-text="mod.title || mod.name"></div>
                                    <div class="text-[10px] text-slate-400" x-text="mod.description || mod.module"></div>
                                </td>

                                {{-- View --}}
                                <td class="px-2.5 py-2.5 text-center">
                                    <template x-if="mod.actions.view">
                                        <input type="checkbox"
                                               :value="mod.actions.view.code"
                                               x-model="selectedPermissions"
                                               class="h-4 w-4 rounded border-slate-300 text-brand-green-600 focus:ring-brand-green-500">
                                    </template>
                                    <template x-if="!mod.actions.view">
                                        <span class="text-slate-300 font-bold">—</span>
                                    </template>
                                </td>

                                {{-- Create --}}
                                <td class="px-2.5 py-2.5 text-center">
                                    <template x-if="mod.actions.create">
                                        <input type="checkbox"
                                               :value="mod.actions.create.code"
                                               x-model="selectedPermissions"
                                               class="h-4 w-4 rounded border-slate-300 text-brand-green-600 focus:ring-brand-green-500">
                                    </template>
                                    <template x-if="!mod.actions.create">
                                        <span class="text-slate-300 font-bold">—</span>
                                    </template>
                                </td>

                                {{-- Edit --}}
                                <td class="px-2.5 py-2.5 text-center">
                                    <template x-if="mod.actions.update">
                                        <input type="checkbox"
                                               :value="mod.actions.update.code"
                                               x-model="selectedPermissions"
                                               class="h-4 w-4 rounded border-slate-300 text-brand-green-600 focus:ring-brand-green-500">
                                    </template>
                                    <template x-if="!mod.actions.update">
                                        <span class="text-slate-300 font-bold">—</span>
                                    </template>
                                </td>

                                {{-- Delete --}}
                                <td class="px-2.5 py-2.5 text-center">
                                    <template x-if="mod.actions.delete">
                                        <input type="checkbox"
                                               :value="mod.actions.delete.code"
                                               x-model="selectedPermissions"
                                               class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                    </template>
                                    <template x-if="!mod.actions.delete">
                                        <span class="text-slate-300 font-bold">—</span>
                                    </template>
                                </td>

                                {{-- Export --}}
                                <td class="px-2.5 py-2.5 text-center">
                                    <template x-if="mod.actions.export">
                                        <input type="checkbox"
                                               :value="mod.actions.export.code"
                                               x-model="selectedPermissions"
                                               class="h-4 w-4 rounded border-slate-300 text-brand-green-600 focus:ring-brand-green-500">
                                    </template>
                                    <template x-if="!mod.actions.export">
                                        <span class="text-slate-300 font-bold">—</span>
                                    </template>
                                </td>

                                {{-- Import --}}
                                <td class="px-2.5 py-2.5 text-center">
                                    <template x-if="mod.actions.import">
                                        <input type="checkbox"
                                               :value="mod.actions.import.code"
                                               x-model="selectedPermissions"
                                               class="h-4 w-4 rounded border-slate-300 text-brand-green-600 focus:ring-brand-green-500">
                                    </template>
                                    <template x-if="!mod.actions.import">
                                        <span class="text-slate-300 font-bold">—</span>
                                    </template>
                                </td>

                                {{-- Approve / Manage --}}
                                <td class="px-2.5 py-2.5 text-center">
                                    <template x-if="mod.actions.approve || mod.actions.manage || mod.actions.backup">
                                        <input type="checkbox"
                                               :value="(mod.actions.approve || mod.actions.manage || mod.actions.backup).code"
                                               x-model="selectedPermissions"
                                               class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    </template>
                                    <template x-if="!mod.actions.approve && !mod.actions.manage && !mod.actions.backup">
                                        <span class="text-slate-300 font-bold">—</span>
                                    </template>
                                </td>

                                {{-- Module Toggle All --}}
                                <td class="px-3 py-2.5 text-right whitespace-nowrap">
                                    <button type="button"
                                            @click="toggleModuleActions(mod, !isModuleAllSelected(mod))"
                                            class="text-[10px] font-semibold px-2 py-0.5 rounded border border-slate-200 hover:bg-slate-100 transition"
                                            :class="isModuleAllSelected(mod) ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'text-slate-600'">
                                        <span x-text="isModuleAllSelected(mod) ? 'Deselect' : 'Select All'"></span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Financial & Cost Data (never included by "Select All Actions") --}}
            <div class="rounded-lg border border-amber-200 bg-amber-50/40" x-show="(financialPermissions || []).length > 0">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-amber-200 px-3.5 py-2.5">
                    <div>
                        <h5 class="text-xs font-bold uppercase tracking-wider text-slate-800">Financial &amp; Cost Data</h5>
                        <p class="text-[11px] text-slate-500">Buying price, supplier cost, and profit margin are sensitive. Product or analytics access does not include them — grant each one explicitly.</p>
                    </div>
                    <button type="button"
                            @click="toggleFinancial(!isFinancialAllSelected())"
                            class="text-[10px] font-semibold px-2 py-0.5 rounded border border-slate-200 bg-white hover:bg-slate-100 transition"
                            :class="isFinancialAllSelected() ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'text-slate-600'">
                        <span x-text="isFinancialAllSelected() ? 'Deselect Section' : 'Select All in Section'"></span>
                    </button>
                </div>
                <div class="grid gap-1.5 p-3 sm:grid-cols-2">
                    <div class="flex items-start gap-2 rounded p-1.5 text-xs">
                        <input type="checkbox" disabled
                               :checked="selectedPermissions.includes('products.view')"
                               class="mt-0.5 h-4 w-4 rounded border-slate-300 text-slate-400">
                        <span>
                            <span class="font-semibold text-slate-700">View Selling Price</span>
                            <span class="block text-[10px] text-slate-500">Included with “View Products” in the table above.</span>
                        </span>
                    </div>
                    <template x-for="perm in financialPermissions" :key="perm.code">
                        <label class="flex items-start gap-2 rounded p-1.5 text-xs cursor-pointer hover:bg-white">
                            <input type="checkbox"
                                   :value="perm.code"
                                   x-model="selectedPermissions"
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                            <span>
                                <span class="font-semibold text-slate-800" x-text="perm.label"></span>
                                <span class="block text-[10px] text-slate-500" x-text="perm.description"></span>
                                <span class="block font-mono text-[10px] text-slate-400" x-text="perm.code"></span>
                            </span>
                        </label>
                    </template>
                </div>
            </div>
        </div>

        {{-- TAB 2: Custom Sidebar Access Control --}}
        <div x-show="subTab === 'sidebar'" class="space-y-3">
            {{-- Search & Global Controls Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50 p-3 rounded-lg border border-slate-200">
                <div class="relative flex-1">
                    <input type="text"
                           x-model="sidebarQuery"
                           placeholder="Search navigation pages (e.g. Orders, Products, SMTP)..."
                           class="admin-control w-full text-xs pl-8">
                    <svg class="h-4 w-4 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="toggleAllSidebar(true)" class="rounded px-2.5 py-1.5 text-[11px] font-semibold bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 transition">
                        Select All Pages
                    </button>
                    <button type="button" @click="toggleAllSidebar(false)" class="rounded px-2.5 py-1.5 text-[11px] font-semibold bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 transition">
                        Deselect All
                    </button>
                </div>
            </div>

            {{-- Explanatory Note --}}
            <p class="text-[11px] text-slate-500 italic">
                * Note: Sidebar visibility controls navigation item presence in the user interface. Backend authorization middleware strictly blocks direct URL access to unpermitted modules regardless of visibility.
            </p>

            {{-- Sidebar Sections List --}}
            <div class="space-y-2.5 max-h-[380px] overflow-y-auto pr-1">
                <template x-for="(section, sIdx) in filteredSidebarSections()" :key="sIdx">
                    <div class="rounded-lg border border-slate-200 bg-white shadow-2xs overflow-hidden">
                        {{-- Section Header --}}
                        <div class="flex items-center justify-between bg-slate-50/80 px-3.5 py-2.5 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="toggleSectionCollapse(sIdx)" class="text-slate-400 hover:text-slate-600">
                                    <svg class="h-3.5 w-3.5 transition-transform" :class="isSectionCollapsed(sIdx) ? '-rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <span class="font-bold text-xs uppercase tracking-wider text-slate-700" x-text="section.label"></span>
                                <span class="text-[10px] text-slate-400" x-text="'(' + section.items.length + ' pages)'"></span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button"
                                        @click="toggleSectionSidebar(section, !isSectionAllSelected(section))"
                                        class="text-[11px] font-medium text-brand-green-700 hover:underline">
                                    <span x-text="isSectionAllSelected(section) ? 'Deselect Section' : 'Select Section'"></span>
                                </button>
                            </div>
                        </div>

                        {{-- Section Pages Grid --}}
                        <div x-show="!isSectionCollapsed(sIdx)" class="p-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                            <template x-for="item in section.items" :key="item.label">
                                <label class="flex items-center gap-2 p-1.5 rounded hover:bg-slate-50 cursor-pointer text-xs">
                                    <input type="checkbox"
                                           :value="item.label"
                                           x-model="selectedSidebar"
                                           class="h-4 w-4 rounded border-slate-300 text-brand-green-600 focus:ring-brand-green-500">
                                    <span class="text-slate-800" x-text="item.label"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Hidden form bindings for Alpine data arrays to ensure submit captures custom configuration --}}
    <template x-if="permissionMode === 'custom'">
        <div>
            <template x-for="pCode in selectedPermissions" :key="pCode">
                <input type="hidden" name="permissions[]" :value="pCode">
            </template>
            <template x-for="sItem in selectedSidebar" :key="sItem">
                <input type="hidden" name="sidebar_access[]" :value="sItem">
            </template>
        </div>
    </template>
</div>
