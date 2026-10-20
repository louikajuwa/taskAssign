@extends('layouts.app')

@php
    // User context supplied by TestplanController@index
    $email = $userEmail;
    $allowedRoles = ['Team Leader (TL)', 'Project Manager (PM) & Integrator'];
@endphp

@section('title', 'Test Plans - TaskAssign')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/quill@1.2.2/dist/quill.snow.css" rel="stylesheet" integrity="sha384-qzUWifQ7cVwAB6J0e8EXfQZEg0zu5ehnXFen04YgBxTwWv0jHm2GrFUXhsICZNGe" crossorigin="anonymous">
<style>
    /* Quill Editor Custom Styles — Add Modal */
    #description-editor-container {
        border: 1px solid #d1d5db;
        border-radius: 0 0 0.5rem 0.5rem;
        background: #f9fafb;
        min-height: 220px;
    }
    #description-editor-container .ql-editor {
        min-height: 200px;
        font-size: 14px;
        color: #1a1a2e;
        line-height: 1.7;
    }
    #description-editor-container .ql-editor.ql-blank::before {
        font-style: normal;
        color: #9ca3af;
    }
    #description-toolbar {
        border: 1px solid #d1d5db;
        border-bottom: none;
        border-radius: 0.5rem 0.5rem 0 0;
        background: #f3f4f6;
    }
    /* Quill Editor Custom Styles — Edit (Swal) Modal */
    #edit-description-editor {
        border: 1px solid #e5e7eb;
        border-radius: 0 0 0.75rem 0.75rem;
        background: #f9fafb;
        min-height: 180px;
    }
    #edit-description-editor .ql-editor {
        min-height: 160px;
        font-size: 14px;
        line-height: 1.7;
    }
    #edit-description-editor .ql-editor.ql-blank::before {
        font-style: normal;
        color: #9ca3af;
    }
    #edit-description-toolbar {
        border: 1px solid #e5e7eb;
        border-bottom: none;
        border-radius: 0.75rem 0.75rem 0 0;
        background: #f3f4f6;
    }
    /* Description view in modals */
    .desc-html-view {
        font-size: 14px;
        line-height: 1.7;
        color: #374151;
    }
    .desc-html-view p { margin-bottom: 0.5em; }
    .desc-html-view ul, .desc-html-view ol { padding-left: 1.5em; margin-bottom: 0.5em; }
    .desc-html-view strong { font-weight: 700; }
    .desc-html-view em { font-style: italic; }
    .desc-html-view a { color: #0083bb; text-decoration: underline; }
    .desc-html-view h1, .desc-html-view h2, .desc-html-view h3 { font-weight: 700; margin: 0.5em 0; }
    .desc-html-view blockquote { border-left: 4px solid #d1d5db; padding-left: 1em; color: #6b7280; }
    /* Form Validation Error Styles */
    .field-validation-error {
        animation: shake-field 0.35s ease;
    }
    .field-validation-error input,
    .field-validation-error textarea,
    .field-validation-error select,
    .field-validation-error .ql-container,
    .field-validation-error .ql-toolbar {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239,68,68,0.12) !important;
    }
    .field-error-msg {
        display: block;
        color: #ef4444;
        font-size: 11px;
        font-weight: 600;
        margin-top: 4px;
        padding-left: 2px;
    }
    @keyframes shake-field {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        50% { transform: translateX(5px); }
        75% { transform: translateX(-3px); }
    }
    /* Swal Edit Modal — Validation Error Styles (mirrors myForm) */
    .swal-field-validation-error {
        animation: shake-field 0.35s ease;
    }
    .swal-field-validation-error input,
    .swal-field-validation-error textarea,
    .swal-field-validation-error select,
    .swal-field-validation-error .ql-container,
    .swal-field-validation-error .ql-toolbar {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239,68,68,0.12) !important;
    }
    /* Full-width Description Editor — Add Modal */
    #description-editor-fw {
        border: 1px solid #d1d5db;
        border-radius: 0 0 0.5rem 0.5rem;
        background: #f9fafb;
        min-height: 220px;
    }
    #description-editor-fw .ql-editor {
        min-height: 200px;
        font-size: 14px;
        color: #1a1a2e;
        line-height: 1.7;
    }
    #description-editor-fw .ql-editor.ql-blank::before {
        font-style: normal;
        color: #9ca3af;
    }
    #description-toolbar-fw {
        border: 1px solid #d1d5db;
        border-bottom: none;
        border-radius: 0.5rem 0.5rem 0 0;
        background: #f3f4f6;
    }
    /* Description read-only Quill view in modal */
    #desc-view-quill-container {
        border: none !important;
        padding: 0 !important;
    }
    #desc-view-quill-container .ql-editor {
        padding: 0 !important;
        font-size: 14px;
        line-height: 1.7;
        color: #374151;
        min-height: auto;
    }
</style>
<div class="p-3 sm:p-8 gap-4 flex flex-col">
    <div id="tracker-header-section"
        class="flex flex-col md:flex-row items-center justify-between gap-6 bg-white p-4 sm:p-6 rounded-xl border border-gray-100 shadow-sm transition-all duration-300">

        <div class="flex items-center gap-5">
            <div class="relative flex-shrink-0">

                <div class="w-16 h-16 rounded-2xl bg-brand-50 flex items-center justify-center">
                    <svg class="w-9 h-9 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>

            <div class="space-y-1">
                <h2 class="text-xl font-bold text-slate-800">Testing Progress Tracker</h2>
                <p class="text-sm text-slate-500 max-w-md leading-relaxed">
                    Track the progress of testing requests by adding new requests, assigning testers, and monitoring
                    progress. Use search to quickly find requests.
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-3 w-full md:w-auto md:min-w-[160px]">

{{-- Category management lives on its own admin-only page at /admin/categories. --}}

            <button type="button" id="openModal"
                class="flex items-center justify-between px-4 py-2 bg-brand-500 hover:bg-brand-600 transition-colors text-white rounded-lg font-medium text-sm focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
                <span>New Request</span>
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </button>
<div id="modalContainer" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="addModalTitle">
    <div id="modalOverlay" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

    <div class="flex items-center justify-center min-h-screen px-2 sm:px-4 py-4 sm:py-10">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl 2xl:max-w-5xl overflow-hidden z-10">
            
            <div class="bg-gray-50 px-4 sm:px-8 py-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h1 id="addModalTitle" class="text-xl font-bold text-gray-900">Add a Test Plan</h1>
                    <p class="text-sm text-gray-500">Fill in the details to register a new Test Plan assignment.</p>
                </div>
                <button type="button" aria-label="Close" class="closeModal p-2 rounded-full text-gray-400 hover:bg-gray-200 hover:text-gray-700 transition-colors">
                    <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form id="myForm" class="p-4 sm:p-8">
                <!-- Validation error banner (hidden by default) -->
                <div id="form-error-banner" role="alert" class="hidden mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-sm font-bold text-red-700">Please fix the following errors:</span>
                    </div>
                    <ul id="form-error-list" class="list-disc list-inside text-xs text-red-600 space-y-1 ml-7"></ul>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <div class="space-y-5">
                        <div id="field-wrap-title">
                            <label for="titleDisplay" class="block text-sm font-semibold text-gray-700 mb-1">Title <span class="text-red-400">*</span></label>
                            <input type="text" id="titleDisplay" name="titleDisplay" class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 outline-none transition-all" />
                        </div>


        <div>
                                <label for="categoryTypeToggle" class="block text-sm font-semibold text-gray-700 mb-1">Category</label>
                                <div class="relative" id="categoryTypeWrapper">
                                    <input type="hidden" name="categoryType" id="categoryTypeValue">
                                    <button type="button" id="categoryTypeToggle" aria-haspopup="listbox" aria-expanded="false" data-dropdown-toggle
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg cursor-pointer flex justify-between items-center text-left focus-visible:ring-2 focus-visible:ring-brand-500">
                                        <span id="categoryTypeLabel" class="text-gray-500 text-sm">Select Category</span>
                                        <svg class="w-4 h-4 text-gray-400" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div id="categoryTypeDropdown" data-dropdown
                                        class="absolute left-0 right-0 z-50 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl hidden custom-scrollbar">
                                        <div class="sticky top-0 bg-white border-b border-gray-100 p-2">
                                            <input type="text" id="categoryTypeSearch" placeholder="Search categories..." aria-label="Search categories"
                                                class="w-full px-2 py-1.5 text-sm bg-gray-50 border border-gray-200 rounded outline-none focus:border-brand-400">
                                        </div>
                                        <div id="categoryTypeOptions" class="max-h-[160px] overflow-y-auto"></div>
                                    </div>
                                </div>
                            </div>

       <div id="field-wrap-caseInfo">
                            <label for="caseInformation" class="block text-sm font-semibold text-gray-700 mb-1">Case Information <span class="text-red-400">*</span></label>
                            <textarea id="caseInformation" name="caseInformation" rows="3" class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 outline-none transition-all resize-none"></textarea>
                        </div>






                        <div class="p-4 bg-brand-50/50 rounded-xl border-2 border-dashed border-brand-200">
                            <span class="block text-sm font-semibold text-brand-900 mb-2">Attach Files <span class="text-xs font-normal text-gray-400">(optional)</span></span>
                            <div id="previewList" class="flex flex-wrap gap-2 mb-2 hidden"></div>
                            <label for="fileInput" class="flex flex-col items-center justify-center w-full h-20 bg-white rounded-lg cursor-pointer border border-gray-200 hover:shadow-md transition-all focus-within:ring-2 focus-within:ring-brand-500">
                                <svg class="w-6 h-6 text-brand-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round" /></svg>
                                <span class="text-xs text-gray-500 font-medium">Click to add files</span>
                                <input type="file" id="fileInput" class="sr-only" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip">
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div id="field-wrap-versionType">
                                <label for="versionType" class="block text-sm font-semibold text-gray-700 mb-1">Version Type <span class="text-red-400">*</span></label>
                                <select id="versionType" name="versionType" class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg outline-none transition-all">
                                    <option value="">Select Type</option>
                                    <option value="sandbox">Sandbox</option>
                                    <option value="alpha">Alpha</option>
                                    <option value="beta">Beta</option>
                                    <option value="live">Live</option>
                                </select>
                            </div>
                            <div>
                                <label for="versionNumber" class="block text-sm font-semibold text-gray-700 mb-1">Version No.</label>
                                <input type="text" id="versionNumber" name="versionNumber" class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 outline-none" />
                            </div>
                        </div>
                    </div>

        <div class="space-y-6 md:max-h-[500px] overflow-y-auto pr-1 sm:pr-4 custom-scrollbar">
    
<div class="grid grid-cols-2 gap-4">
    <div id="field-wrap-typeOfStatus">
        <label for="typeOfStatus" class="block text-sm font-semibold text-gray-700 mb-1">Type of Status <span class="text-red-400">*</span></label>
        <select id="typeOfStatus" name="typeOfStatus" class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 outline-none transition-all text-sm">
            <option value="">Select Type</option>
            <option value="New">New</option>
            <option value="Bug">Bug</option>
        </select>
    </div>

    <div>
        <span class="block text-sm font-semibold text-gray-700 mb-1">Set Priority</span>
        <div class="flex items-center h-[42px] px-3 bg-gray-50 border border-gray-300 rounded-lg">
            <label class="flex items-center gap-3 cursor-pointer w-full group">
                <input type="checkbox" id="priority" name="priority" 
                       class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500 focus:ring-offset-0 transition-all cursor-pointer">
                <span class="text-sm font-medium text-gray-600 group-hover:text-gray-900 transition-colors">Mark as Priority</span>
            </label>
        </div>
    </div>
</div>
    
    <div class="relative group">
        <label for="asignToDeVId" class="block text-sm font-semibold text-gray-700 mb-1">Assign To Dev</label>
        <div class="relative">
            <input type="tel" id="asignToDeVId" name="asignToDeVId" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 outline-none bg-white transition-all" 
                placeholder="Search email..." autocomplete="off" />
            <ul id="autocomplete-list-asignToDeVId" data-dropdown
                class="absolute left-0 right-0 z-[100] mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-52 overflow-y-auto hidden">
            </ul>
        </div>
        <div id="asignToDeVIdContainer" class="flex flex-wrap gap-2 mt-2"></div>
    </div>

    <div class="relative group">
        <label for="generecTesterId" class="block text-sm font-semibold text-gray-700 mb-1">Generic Tester</label>
        <div class="relative">
            <input type="tel" id="generecTesterId" name="generecTesterId" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 outline-none bg-white transition-all" 
                placeholder="Search email..." autocomplete="off" />
            <ul id="autocomplete-list-generecTesterId" data-dropdown
                class="absolute left-0 right-0 z-[100] mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-52 overflow-y-auto hidden">
            </ul>
        </div>
        <div id="generecTesterIdContainer" class="flex flex-wrap gap-2 mt-2"></div>
    </div>

    <div class="relative group">
        <label for="sandboxTesterId" class="block text-sm font-semibold text-gray-700 mb-1">Sandbox Tester</label>
        <div class="relative">
            <input type="tel" id="sandboxTesterId" name="sandboxTesterId" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 outline-none bg-white transition-all" 
                placeholder="Search email..." autocomplete="off" />
            <ul id="autocomplete-list-sandboxTesterId" data-dropdown
                class="absolute left-0 right-0 z-[100] mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-52 overflow-y-auto hidden">
            </ul>
        </div>
        <div id="sandboxTesterIdContainer" class="flex flex-wrap gap-2 mt-2"></div>
    </div>

    <div class="relative group">
        <label for="alphaTesterId" class="block text-sm font-semibold text-gray-700 mb-1">Alpha Tester</label>
        <div class="relative">
            <input type="tel" id="alphaTesterId" name="alphaTesterId" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 outline-none bg-white transition-all" 
                placeholder="Search email..." autocomplete="off" />
            <ul id="autocomplete-list-alphaTesterId" data-dropdown
                class="absolute left-0 right-0 z-[100] mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-52 overflow-y-auto hidden">
            </ul>
        </div>
        <div id="alphaTesterIdContainer" class="flex flex-wrap gap-2 mt-2"></div>
    </div>

    <div class="relative group">
        <label for="betaTesterId" class="block text-sm font-semibold text-gray-700 mb-1">Beta Tester</label>
        <div class="relative">
            <input type="tel" id="betaTesterId" name="betaTesterId" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 outline-none bg-white transition-all" 
                placeholder="Search email..." autocomplete="off" />
            <ul id="autocomplete-list-betaTesterId" data-dropdown
                class="absolute left-0 right-0 z-[100] mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-52 overflow-y-auto hidden">
            </ul>
        </div>
        <div id="betaTesterIdContainer" class="flex flex-wrap gap-2 mt-2"></div>
    </div>

    <div class="relative group">
        <label for="liveTesterId" class="block text-sm font-semibold text-gray-700 mb-1">Live Tester</label>
        <div class="relative">
            <input type="tel" id="liveTesterId" name="liveTesterId" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 outline-none bg-white transition-all" 
                placeholder="Search email..." autocomplete="off" />
            <ul id="autocomplete-list-liveTesterId" data-dropdown
                class="absolute left-0 right-0 z-[100] mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-52 overflow-y-auto hidden">
            </ul>
        </div>
        <div id="liveTesterIdContainer" class="flex flex-wrap gap-2 mt-2"></div>
    </div>
</div>



                </div>

                <!-- Full-width Description Editor below the grid -->
                <div id="field-wrap-description-fw" class="mt-8">
                    <span id="description-label-fw" class="block text-sm font-semibold text-gray-700 mb-2">Description <span class="text-red-400">*</span></span>
                    <div id="description-toolbar-fw">
                        <span class="ql-formats">
                            <button class="ql-bold"></button>
                            <button class="ql-italic"></button>
                            <button class="ql-underline"></button>
                            <button class="ql-strike"></button>
                        </span>
                        <span class="ql-formats">
                            <button class="ql-header" value="1"></button>
                            <button class="ql-header" value="2"></button>
                        </span>
                        <span class="ql-formats">
                            <button class="ql-list" value="ordered"></button>
                            <button class="ql-list" value="bullet"></button>
                        </span>
                        <span class="ql-formats">
                            <button class="ql-link"></button>
                            <button class="ql-blockquote"></button>
                            <button class="ql-code-block"></button>
                        </span>
                        <span class="ql-formats">
                            <button class="ql-clean"></button>
                        </span>
                    </div>
                    <div id="description-editor-fw"></div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 mt-6 border-t border-gray-100">
                    <button type="button" class="closeModal px-6 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-lg transition-all border border-gray-300">Cancel</button>
                    <button type="submit" id="submitRequestBtn" class="px-8 py-2 bg-brand-600 text-white text-sm font-bold rounded-lg hover:bg-brand-700 transition-all shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>



<div class="flex flex-col gap-3 p-1">
    <div class="flex flex-col">
        <span class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-0.5">
            User Email
        </span>
        <h3 class="text-base font-bold text-gray-900 leading-tight tracking-tight break-all">
            {{ $email }}
        </h3>
    </div>

    <div class="flex flex-col">
        <span class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-1">
            Access Role
        </span>
        <div class="flex">
            <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider bg-brand-50 text-brand-700 border border-brand-100 shadow-sm">
                <svg class="w-3 h-3 mr-1 opacity-70" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M2.166 4.9L10 1.55l7.834 3.35a1 1 0 01.666.92v6.57a8 8 0 01-4.045 6.958L10 20l-4.455-2.202A8 8 0 012 10.84V5.82a1 1 0 01.666-.92zM9 7.001V11a1 1 0 102 0V7.001a1 1 0 10-2 0zM10 15a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                {{ $userRole }}
            </span>
        </div>
    </div>
</div>
        </div>

    </div>
    <div class="bg-white border rounded-xl shadow-sm px-2 sm:px-5 py-4 sm:py-8">

        <div
            class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 p-2 sm:p-4 bg-white">

            <div class="flex items-center">
                <h1 class="text-xl sm:text-2xl font-bold pl-1 sm:pl-3 text-gray-800">New Testing Requests</h1>

                <button type="button" id="toggle-tracker-btn" onclick="toggleTrackerSection()" 
                        class="ml-3 flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-gray-500 hover:text-brand-600 bg-gray-50 hover:bg-brand-50 border border-gray-200 hover:border-brand-200 rounded-lg transition-all"
                        title="Toggle Tracker Header">
                    <svg id="toggle-tracker-icon" aria-hidden="true" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                    </svg>
                    <span id="toggle-tracker-label">Focus Table</span>
                </button>
            </div>

            <div
                class="flex flex-col md:flex-row items-stretch md:items-center gap-3 w-full md:w-auto md:pr-3">





                <div id="filter-container" class="flex flex-wrap gap-2 bg-white" role="group" aria-label="Filter requests">
                    <button type="button" data-filter="all" aria-pressed="true"
                        class="filter-btn flex items-center justify-center px-3 py-2 sm:px-6 sm:py-3 bg-brand-500 text-white rounded-xl font-bold text-xs sm:text-sm tracking-wide shadow-sm transition-all border-2 border-brand-500">
                        All Requests
                    </button>

                    <button type="button" data-filter="Open" aria-pressed="false"
                        class="filter-btn flex items-center justify-center space-x-2 px-3 py-2 sm:px-6 sm:py-3 bg-transparent border-2 border-[#5D6778] text-[#5D6778] rounded-xl font-bold text-xs sm:text-sm tracking-wide hover:bg-gray-50 transition-all">
                        <span>In Progress</span>
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" />
                        </svg>
                    </button>

                    <button type="button" data-filter="Completed" aria-pressed="false"
                        class="filter-btn flex items-center justify-center space-x-2 px-3 py-2 sm:px-6 sm:py-3 bg-transparent border-2 border-[#5D6778] text-[#5D6778] rounded-xl font-bold text-xs sm:text-sm tracking-wide hover:bg-gray-50 transition-all">
                        <span>Completed</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                     <button type="button" data-filter="MyCreated" aria-pressed="false"
                        class="filter-btn flex items-center justify-center space-x-2 px-3 py-2 sm:px-6 sm:py-3 bg-transparent border-2 border-[#5D6778] text-[#5D6778] rounded-xl font-bold text-xs sm:text-sm tracking-wide hover:bg-gray-50 transition-all">
                        <span>My Created</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                    <button type="button" data-filter="AssignToMe" aria-pressed="false"
                        class="filter-btn flex items-center justify-center space-x-2 px-3 py-2 sm:px-6 sm:py-3 bg-transparent border-2 border-[#5D6778] text-[#5D6778] rounded-xl font-bold text-xs sm:text-sm tracking-wide hover:bg-gray-50 transition-all">
                        <span>Assign To Me</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div class="px-2 sm:px-6 w-full">
            <div class="flex flex-col md:flex-row gap-2">
                <!-- Search input and column filter container -->
                <div class="flex flex-col sm:flex-row gap-2 w-full md:mr-2">
                    <div class="relative flex-grow">
                        <label for="searchInput" class="sr-only">Search via title</label>
                        <input type="text" placeholder="Search Via Title..."
                            class="border border-gray-300 rounded-lg py-3 text-sm w-full bg-gray-100 pl-10 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none" id="searchInput">
                        <svg aria-hidden="true" class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 search-custom" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                        </svg>
                    </div>
                    <div class="relative w-full sm:w-auto sm:min-w-[180px]" id="categoryFilterWrapper">
                        <button type="button" id="categoryFilterToggle" aria-label="Filter by category" aria-haspopup="listbox" aria-expanded="false"
                            class="w-full h-full px-3 py-3 sm:py-2 bg-gray-50 border border-gray-300 rounded-lg cursor-pointer flex justify-between items-center text-left focus-visible:ring-2 focus-visible:ring-brand-500">
                            <span id="categoryFilterLabel" class="text-gray-500 text-sm">All Categories</span>
                            <svg class="w-4 h-4 text-gray-400" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="categoryFilterDropdown" data-dropdown
                            class="absolute left-0 right-0 z-50 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl hidden custom-scrollbar">
                            <div class="sticky top-0 bg-white border-b border-gray-100 p-2">
                                <input type="text" id="categoryFilterSearch" placeholder="Search categories..." aria-label="Search categories"
                                    class="w-full px-2 py-1.5 text-sm bg-gray-50 border border-gray-200 rounded outline-none focus:border-brand-400">
                            </div>
                            <div id="categoryFilterOptions" class="max-h-[160px] overflow-y-auto"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- Table design -->
        <div id="table-scroll-container" class="overflow-x-auto overflow-y-auto max-h-[70vh] mx-0 my-4 sm:m-6 border border-gray-200 rounded-lg transition-all duration-300">
            <table id="leadsTable" class="min-w-full divide-y divide-gray-200">
                <caption class="sr-only">New testing requests</caption>
                <thead class="bg-[#F8F9FA] border-b border-gray-100 sticky top-0 z-10">
                    <tr>
                                 <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-[#6E7C87] tracking-tight">
                   Created By
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-[#6E7C87] tracking-tight">
                            Title
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-[#6E7C87] tracking-tight">
                            Description of request
                        </th>
                               <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-[#6E7C87] tracking-tight">
                  Version Information
                        </th>
                              <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-[#6E7C87] tracking-tight">
                          Main  Status
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-[#6E7C87] tracking-tight">
                            Assigned to Dev
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-[#6E7C87] tracking-tight">
              Generic Tester
                        </th>
                                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-[#6E7C87] tracking-tight">
              Sandbox Tester
                        </th>
                            <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-[#6E7C87] tracking-tight">
                            Alpha Tester
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-[#6E7C87] tracking-tight">
                            Beta Tester
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-[#6E7C87] tracking-tight">
                            Live Tester
                        </th>
                                 <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-[#6E7C87] tracking-tight">
                    Created
                        </th>
                                          <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-[#6E7C87] tracking-tight">
              Main Action
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <!-- Table rows will be populated here dynamically -->
                </tbody>
            </table>
        </div>

        <!-- Pagination design -->
        <div class="flex flex-col md:flex-row gap-3 justify-between items-center px-2 sm:px-0 sm:ml-8">
            <nav class="flex flex-wrap items-center justify-center gap-1 bg-white rounded-lg pagination-buttons" aria-label="Test plans pagination">
                <!-- Pagination buttons will be dynamically generated here -->
            </nav>
            <div id="table-info" class="text-sm text-gray-500 md:mr-12" aria-live="polite"></div>
        </div>

    </div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/quill@1.2.2/dist/quill.min.js" integrity="sha384-2gJolQNGgCD23nZ8/1ViIki04dIJgEdM4PW2siNjl1aSGFxYagGMprL30Rxfeje8" crossorigin="anonymous"></script>
<script>

let categories2 = [];
let filteredData2 = [];

// Common Toast Configuration
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true
});

/**
 * ───────────────────────────────────────────────────────────────────────
 *  REST API ADAPTER (Laravel migration shim)
 *  The legacy frontend talked to a single endpoint (/testplan/data) using a
 *  numeric `type` discriminator. The Laravel backend is fully RESTful, so this
 *  one function maps each legacy `type` to its REST route + verb. CSRF travels
 *  in the X-CSRF-TOKEN header. Returns a jqXHR so existing .done()/.fail()/
 *  .then() chains keep working unchanged.
 * ───────────────────────────────────────────────────────────────────────
 */
function legacyApi(type, payload = {}) {
    payload = { ...payload };
    // Legacy used "userId" for the user being acted on; controllers expect "targetUserId".
    if (payload.userId !== undefined && payload.targetUserId === undefined) {
        payload.targetUserId = payload.userId;
    }

    const csrf = $('meta[name="csrf-token"]').attr('content');
    let method = 'POST';
    let url = '';
    let asQuery = false;       // GET query string vs JSON body
    let body = payload;

    switch (parseInt(type, 10)) {
        case 1:   method = 'GET';    url = '/api/users/search';      body = { q: payload.search || '' }; asQuery = true; break;
        case 2:   method = 'POST';   url = '/api/testplans'; break;
        case 3:   method = 'POST';   url = '/api/testplans/list'; break;
        case 4:   method = 'PUT';    url = '/api/assignments/update'; break;
        case 5:   method = 'DELETE'; url = '/api/assignments'; break;
        case 6:   method = 'POST';   url = '/api/assignments'; break;
        case 7:   method = 'DELETE'; url = '/api/testplans/' + encodeURIComponent(payload.testplanId); body = null; break;
        case 8:   method = 'PUT';    url = '/api/testplans/' + encodeURIComponent(payload.testplanId); break;
        case 9:   method = 'POST';   url = '/api/comments'; break;
        case 10:  method = 'POST';   url = '/api/comments/list'; break;
        case 100: method = 'GET';    url = '/api/versions';          body = { type: payload.versionType || '' }; asQuery = true; break;
        case 103: method = 'POST';   url = '/api/categories'; break;
        case 104: method = 'GET';    url = '/api/categories';        body = { search: payload.search || '' }; asQuery = true; break;
        case 105: method = 'DELETE'; url = '/api/categories/' + encodeURIComponent(payload.categoryId); body = null; break;
        case 106: method = 'POST';   url = '/api/category-testers'; break;
        case 107: method = 'GET';    url = '/api/category-testers/' + encodeURIComponent(payload.categoryId); body = null; break;
        case 108: method = 'DELETE'; url = '/api/category-testers/' + encodeURIComponent(payload.bindingId); body = null; break;
        case 109: method = 'GET';    url = '/api/categories/simple'; body = { search: payload.search || '' }; asQuery = true; break;
        default:  return $.Deferred().reject({ responseJSON: { message: 'Unknown action type: ' + type } }).promise();
    }

    const opts = {
        method: method,
        url: url,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
    };
    if (asQuery) {
        opts.data = body;
    } else if (body !== null) {
        opts.contentType = 'application/json';
        opts.data = JSON.stringify(body);
    }
    return $.ajax(opts);
}

/**
 * Convenience wrapper that preserves the legacy { success, error, complete }
 * callback style while routing through the REST adapter. Callback signatures
 * match jQuery's: success(data, status, xhr), error(xhr, status, err).
 */
function apiMsg(xhr, fallback) {
    const j = xhr && xhr.responseJSON;
    if (j) {
        if (j.message) return j.message;
        if (j.errors) { const f = Object.values(j.errors).flat()[0]; if (f) return f; }
    }
    if (xhr && xhr.status === 403) return "You don't have permission to do that.";
    if (xhr && xhr.status === 404) return 'The requested item was not found.';
    if (xhr && !xhr.status) return 'Network error. Please check your connection and try again.';
    return fallback || 'Something went wrong. Please try again.';
}

function legacyAjax(type, payload, handlers = {}) {
    const xhr = legacyApi(type, payload);
    if (handlers.success) xhr.done(handlers.success);
    if (handlers.error) xhr.fail(handlers.error);
    if (handlers.complete) xhr.always(handlers.complete);
    return xhr;
}

/**
 * Promise that ALWAYS resolves with the parsed JSON body, even for HTTP error
 * statuses — mirroring window.fetch() semantics so the legacy
 * `if (data.result === 'error')` branches keep working. Rejects only on a true
 * network failure.
 */
function legacyJson(type, payload) {
    return legacyApi(type, payload).then(
        data => data,
        xhr => (xhr && xhr.responseJSON) ? xhr.responseJSON : { result: 'error', message: 'Network error' }
    );
}

/**
 * Uploads File objects to Laravel storage (multipart/form-data) and resolves
 * to an array of public URLs. Replaces the legacy base64-in-JSON pattern.
 */
async function uploadFiles(files) {
    files = Array.from(files || []);
    if (!files.length) return [];
    const fd = new FormData();
    files.forEach(f => fd.append('files[]', f));
    const csrf = $('meta[name="csrf-token"]').attr('content');
    const res = await fetch('/api/files/upload', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: fd
    });
    if (!res.ok) {
        let msg = 'File upload failed.';
        try {
            const j = await res.json();
            msg = j.message || (j.errors && Object.values(j.errors)[0][0]) || msg;
        } catch (e) {}
        throw new Error(msg);
    }
    const data = await res.json();
    return data.urls || [];
}

function handleCategoryAction2(actionType, payload = {}) {
    // Show loading only if request takes longer than 1.5 seconds
    let loadingTriggered = false;
    let errorHandled = false;
    const loadingTimer = setTimeout(() => {
        loadingTriggered = true;
        Swal.fire({
            title: 'Processing...',
            html: 'This is taking a bit longer than usual, please wait.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    }, 1500);

    return legacyApi(actionType, payload)
        .always(function() {
            clearTimeout(loadingTimer);
            if (loadingTriggered && !errorHandled) Swal.close();
        })
        .fail(function(xhr) {
            errorHandled = true;
            let errorMsg = "An internal server error occurred.";
            try {
                const res = xhr.responseJSON || JSON.parse(xhr.responseText);
                errorMsg = res.message || errorMsg;
            } catch(e) {}
            Swal.fire({
                icon: 'error',
                title: 'Action Failed',
                text: errorMsg,
                confirmButtonColor: '#009FE3'
            });
        });
}
function fetchCategoriesLightweight() {
    handleCategoryAction2(109, { search: "" }).done(function(response) {
        if (response.data) {
            categories2 = response.data;
            populateCategoryDropdown('categoryType');
            populateCategoryFilterDropdown();
        }
    });
}

/**
 * Populate a custom category dropdown.
 * @param {string} prefix     - ID prefix: 'categoryType' for create, 'editCategoryType' for edit
 * @param {string} currentVal - Pre-select this value (for edit mode)
 */
function populateCategoryDropdown(prefix, currentVal = '') {
    const $dropdown = $(`#${prefix}Dropdown`);
    const $options = $(`#${prefix}Options`);
    const $label = $(`#${prefix}Label`);
    const $value = prefix === 'categoryType' ? $('#categoryTypeValue') : $('#edit-categoryTypeValue');
    const $toggle = $(`#${prefix}Toggle`);
    const $search = $(`#${prefix}Search`);

    if (!$dropdown.length) return;

    if (currentVal) {
        const currentCat = categories2.find(c => String(c.id) === String(currentVal));
        $label.text(currentCat ? currentCat.category_name : currentVal).removeClass('text-gray-500').addClass('text-gray-800');
        $value.val(currentVal);
    } else {
        $label.text('Select Category').addClass('text-gray-500').removeClass('text-gray-800');
        $value.val('');
    }

    function renderOptions(filterText) {
        $options.empty();
        const term = (filterText || '').toLowerCase();
        const filtered = term ? categories2.filter(c => (c.category_name || '').toLowerCase().includes(term)) : categories2;

        if (filtered.length === 0) {
            $options.append('<div class="px-3 py-3 text-center text-gray-400 text-sm">No categories found</div>');
            return;
        }

        filtered.forEach(cat => {
            const isActive = String(cat.id) === String($value.val());
            const $item = $(`
                <button type="button" role="option" aria-selected="${isActive}" class="block w-full text-left px-3 py-2 cursor-pointer text-sm hover:bg-brand-50 transition-colors ${isActive ? 'bg-brand-50 font-semibold text-brand-700' : 'text-gray-700'}"
                    data-value="${escAttr(cat.category_name)}" data-id="${escAttr(cat.id)}">
                    ${escapeHtml(cat.category_name)}
                </button>
            `);
            $item.on('click', function () {
                const val = $(this).attr('data-value');
                const catId = $(this).attr('data-id');
                $toggle.attr('aria-expanded', 'false');
                $value.val(catId);
                $label.text(val).removeClass('text-gray-500').addClass('text-gray-800');
                $dropdown.addClass('hidden');
                $search.val('');
                if (prefix === 'categoryType' && catId) {
                    autoAssignCategoryTesters(catId);
                }
            });
            $options.append($item);
        });
    }

    renderOptions('');

    $search.off('input.catSearch').on('input.catSearch', function () {
        renderOptions($(this).val());
    });

    $search.off('click.catStop').on('click.catStop', function (e) {
        e.stopPropagation();
    });

    $toggle.off('click.catToggle').on('click.catToggle', function (e) {
        e.stopPropagation();
        const wasHidden = $dropdown.hasClass('hidden');
        $dropdown.toggleClass('hidden');
        $toggle.attr('aria-expanded', String(wasHidden));
        if (wasHidden) {
            $search.val('');
            renderOptions('');
            setTimeout(() => $search.focus(), 50);
        }
    });
}

$(document).on('click', function (e) {
    if (!$(e.target).closest('#categoryTypeWrapper').length) {
        $('#categoryTypeDropdown').addClass('hidden');
    }
    if (!$(e.target).closest('#editCategoryTypeWrapper').length) {
        $('#editCategoryTypeDropdown').addClass('hidden');
    }
    if (!$(e.target).closest('#categoryFilterWrapper').length) {
        $('#categoryFilterDropdown').addClass('hidden');
    }
});

function populateCategoryFilterDropdown() {
    const $dropdown = $('#categoryFilterDropdown');
    const $options  = $('#categoryFilterOptions');
    const $label    = $('#categoryFilterLabel');
    const $toggle   = $('#categoryFilterToggle');
    const $search   = $('#categoryFilterSearch');

    function renderOptions(filterText) {
        $options.empty();
        const term = (filterText || '').toLowerCase();
        const filtered = term
            ? categories2.filter(c => (c.category_name || '').toLowerCase().includes(term))
            : categories2;

        const allActive = !categoryFilter;
        const $allItem = $(`<button type="button" role="option" aria-selected="${allActive}" class="block w-full text-left px-3 py-2 cursor-pointer text-sm hover:bg-brand-50 transition-colors ${allActive ? 'bg-brand-50 font-semibold text-brand-700' : 'text-gray-700'}">All Categories</button>`);
        $allItem.on('click', function () {
            categoryFilter = '';
            $label.text('All Categories').addClass('text-gray-500').removeClass('text-gray-800');
            $dropdown.addClass('hidden');
            $search.val('');
            currentPage = 1;
            fetchData();
        });
        $options.append($allItem);

        if (filtered.length === 0 && term) {
            $options.append('<div class="px-3 py-3 text-center text-gray-400 text-sm">No categories found</div>');
            return;
        }

        filtered.forEach(cat => {
            const isActive = String(cat.category_name) === String(categoryFilter);
            const $item = $(`<button type="button" role="option" aria-selected="${isActive}" class="block w-full text-left px-3 py-2 cursor-pointer text-sm hover:bg-brand-50 transition-colors ${isActive ? 'bg-brand-50 font-semibold text-brand-700' : 'text-gray-700'}">${escapeHtml(cat.category_name)}</button>`);
            $item.on('click', function () {
                categoryFilter = cat.category_name;
                $label.text(cat.category_name).removeClass('text-gray-500').addClass('text-gray-800');
                $dropdown.addClass('hidden');
                $search.val('');
                currentPage = 1;
                fetchData();
            });
            $options.append($item);
        });
    }

    renderOptions('');

    $search.off('input.catFilterSearch').on('input.catFilterSearch', function () {
        renderOptions($(this).val());
    });
    $search.off('click.catFilterStop').on('click.catFilterStop', function (e) {
        e.stopPropagation();
    });
    $toggle.off('click.catFilterToggle').on('click.catFilterToggle', function (e) {
        e.stopPropagation();
        const wasHidden = $dropdown.hasClass('hidden');
        $dropdown.toggleClass('hidden');
        $toggle.attr('aria-expanded', String(wasHidden));
        if (wasHidden) {
            $search.val('');
            renderOptions('');
            setTimeout(() => $search.focus(), 50);
        }
    });
}

// --- Auto-Assignment on Category Selection ---
const roleIdToContainerId = {
    1: 'asignToDeVId',
    2: 'generecTesterId',
    3: 'sandboxTesterId',
    4: 'alphaTesterId',
    5: 'betaTesterId',
    6: 'liveTesterId'
};

function tagExists($container, userId) {
    return $container.children().filter(function () { return $(this).attr('data-user-id') === String(userId); }).length > 0;
}

function userTagHtml(item, fieldId, auto) {
    return `
        <div class="relative group flex items-center bg-sky-500 text-white text-xs px-3 py-1.5 rounded-md shadow-sm mb-1 mr-1" data-user-id="${escAttr(item.userId)}" ${auto ? 'data-auto="true"' : ''}>
            <span class="max-w-[150px] truncate">${escapeHtml(item.email)}</span>
            <button type="button" aria-label="Remove ${escAttr(item.email)}" class="remove-user-tag ml-2 hover:text-red-200 font-bold">&times;</button>
            <input type="hidden" name="${escAttr(fieldId)}[]" value="${escAttr(item.userId)}">
        </div>`;
}

function autoAssignCategoryTesters(categoryId) {
    $('[data-auto="true"]').remove();

    handleCategoryAction2(107, { categoryId: categoryId }).done(function(response) {
        if (response.result !== 'success' || !response.data || response.data.length === 0) return;

        response.data.forEach(item => {
            const fieldId = roleIdToContainerId[item.role_id];
            if (!fieldId) return;

            const $container = $(`#${fieldId}Container`);
            if (tagExists($container, item.userId)) return;

            const $tag = $(userTagHtml(item, fieldId, true));

            $tag.find('.remove-user-tag').on('click', function() {
                $tag.remove();
            });

            $container.append($tag);
        });
    });
}


    const MAX_FILE_SIZE_MB = 20;
    const MAX_TOTAL_SIZE_MB = 200; // 10 files x 20 MB (server limit)
    const MAX_FILES_PER_REQUEST = 10;
    const ALLOWED_EXTENSIONS = ["jpg","jpeg","png","gif","webp","pdf","doc","docx","xls","xlsx","ppt","pptx","txt","csv","zip"];
    const FILE_ACCEPT = ALLOWED_EXTENSIONS.map(e => "." + e).join(",");
    function filterUploadFiles(files, existingCount) {
        const ok = [];
        const problems = [];
        files.forEach(f => {
            if (!isAllowedFile(f)) problems.push(f.name + ': file type not allowed');
            else if (f.size > MAX_FILE_SIZE_MB * 1024 * 1024) problems.push(f.name + ': exceeds ' + MAX_FILE_SIZE_MB + ' MB');
            else if (existingCount + ok.length >= MAX_FILES_PER_REQUEST) problems.push(f.name + ': max ' + MAX_FILES_PER_REQUEST + ' files');
            else ok.push(f);
        });
        if (problems.length) {
            Toast.fire({ icon: 'error', title: 'Some files were not added', html: problems.map(escapeHtml).join('<br>'), timer: 5000 });
        }
        return ok;
    }
    function isAllowedFile(f) { return ALLOWED_EXTENSIONS.includes(String(f.name || "").split(".").pop().toLowerCase()); }

    function previewAttachment(rawUrl) {
        const url = safeUrl(rawUrl);
        if (!url) {
            Swal.fire({ icon: 'error', title: 'Invalid file', text: 'This attachment link cannot be opened.' });
            return;
        }
        const fileNameRaw = url.split('?')[0].split('/').pop();
        const fileName = escapeHtml(fileNameRaw);
        const extRaw = fileNameRaw.split('.').pop().toLowerCase();
        const ext = escapeHtml(extRaw);
        const urlAttr = escAttr(url);
        const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        const videoExts = ['mp4', 'mov', 'avi', 'mkv', 'webm'];
        const pdfExts = ['pdf'];

        let contentHtml = '';
        let modalWidth = 900;
        const isVideo = videoExts.includes(extRaw);
        const isImage = imageExts.includes(extRaw);

        if (isImage) {
            modalWidth = '90%';
            contentHtml = `<img src="${urlAttr}" alt="${fileName}" class="w-full max-h-[80vh] object-contain rounded-xl">`;
        } else if (isVideo) {
            modalWidth = '90%';
            const videoId = 'preview-video-' + Date.now();
            contentHtml = `
                <video id="${videoId}" controls autoplay playsinline class="w-full max-h-[80vh] rounded-xl bg-black" style="min-height: 400px;">
                    <source src="${urlAttr}" type="video/mp4">
                    <source src="${urlAttr}" type="video/webm">
                    <source src="${urlAttr}" type="video/quicktime">
                </video>
                <div id="${videoId}-error" class="hidden flex-col items-center justify-center py-12 text-gray-400">
                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <p class="text-sm font-semibold mb-2">This video format (.${ext}) cannot be played in your browser</p>
                    <p class="text-xs mb-4">Please download the file to view it</p>
                    <a href="${urlAttr}" download target="_blank" rel="noopener noreferrer" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold rounded-xl transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download File
                    </a>
                </div>`;
        } else if (pdfExts.includes(extRaw)) {
            modalWidth = '90%';
            contentHtml = `<iframe src="${urlAttr}" title="${fileName}" class="w-full rounded-xl border border-gray-200" style="height: 80vh;"></iframe>`;
        } else {
            contentHtml = `
                <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-sm font-semibold mb-1">Preview not available for this file type</p>
                    <p class="text-xs">${fileName}</p>
                </div>`;
        }

        Swal.fire({
            width: modalWidth === 900 ? 'min(56rem, 95vw)' : 'min(75rem, 95vw)',
            padding: 0,
            showConfirmButton: false,
            showCloseButton: false,
            background: isVideo ? '#111' : '#fff',
            customClass: {
                popup: 'rounded-2xl shadow-2xl overflow-hidden',
                htmlContainer: 'm-0 p-0'
            },
            html: `
                <div>
                    <div class="flex items-center justify-between px-5 py-3 ${isVideo ? 'bg-black/50' : 'bg-gray-50 border-b border-gray-100'}">
                        <span class="text-sm font-bold ${isVideo ? 'text-gray-300' : 'text-gray-600'} truncate max-w-[60%]">${fileName}</span>
                        <div class="flex items-center gap-3 shrink-0">
                            <a href="${urlAttr}" download target="_blank" rel="noopener noreferrer" class="text-sm font-bold text-brand-500 hover:text-brand-400 flex items-center gap-1.5 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Download
                            </a>
                            <button type="button" aria-label="Close preview" onclick="Swal.close()" class="p-1 rounded-lg ${isVideo ? 'hover:bg-white/10 text-gray-400 hover:text-white' : 'hover:bg-gray-200 text-gray-400 hover:text-gray-700'} transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="${isVideo || isImage ? '' : 'p-5'}">
                        ${contentHtml}
                    </div>
                </div>
            `,
            didOpen: () => {
                if (isVideo) {
                    const videoId = document.querySelector('video[id^="preview-video-"]');
                    if (videoId) {
                        videoId.addEventListener('error', function() {
                            this.classList.add('hidden');
                            const errDiv = document.getElementById(this.id + '-error');
                            if (errDiv) errDiv.classList.remove('hidden');
                            errDiv.classList.add('flex');
                        });
                        const sources = videoId.querySelectorAll('source');
                        sources[sources.length - 1].addEventListener('error', function() {
                            videoId.dispatchEvent(new Event('error'));
                        });
                    }
                }
            }
        });
    }

    let selectedFiles = [];


    let data = []; // Declare a global variable to store the initial data
    let filteredData = []; // Initialize filteredData as an empty array
    let totalEntries = 0;
    let startIndex = 0;
    let endIndex = 0;
    let columnFilter = $('#columnFilter').val();
    let categoryFilter = '';
    let searchText = '';
    let currententity = 0;
    let currentlastentity = 0;

    // Delete permission flag (admins may delete; supplied by the controller)
    const inquiries_delete = {{ $isAdmin ? 'true' : 'false' }};




    // Function to handle AJAX response
    function handleAjaxResponse(response) {
        totalpagesshown = response.pagination.limit;
        endIndex = response.pagination.totalPages;
        totalEntries = response.pagination.totalRecords;
        currentPage = response.pagination.currentPage;
        data = response.data; // Store the initial data in the data variable
        filteredData = data.slice(); // Copy the initial data to filteredData
        currententity = currentPage * totalpagesshown - totalpagesshown + 1;
        endIndex = Math.min(currententity + totalpagesshown - 1, totalEntries);

        displayPage(); // Display the page with the initial data
    }

// List of all autocomplete IDs
const autocompleteIds = [
    "asignToDeVId", 
    "generecTesterId", 
    "sandboxTesterId", 
    "alphaTesterId", 
    "betaTesterId", 
    "liveTesterId"
];



// Initialize events for all autocomplete IDs
autocompleteIds.forEach(id => {
    const $input = $(`#${id}`);
    const $list = $(`#autocomplete-list-${id}`);
    let fieldTimeout;

    $input.on('focus', function () {
        const val = $(this).val();
        if (val.trim() === "") {
            $list.empty().append('<li class="p-3 text-xs text-gray-400 text-center">Type at least 2 characters to search</li>').removeClass('hidden');
        }
    });

    $input.on('keyup', function () {
        const val = $(this).val();

        clearTimeout(fieldTimeout);
        fieldTimeout = setTimeout(() => {
            if (val.trim().length >= 2) {
                handledisplaylist(id, val.trim());
            } else if (val.length > 0) {
                $list.empty().append('<li class="p-3 text-xs text-gray-400 text-center">Type at least 2 characters to search</li>').removeClass('hidden');
            } else {
                $list.addClass('hidden').empty();
            }
        }, 300);
    });
});

// ✅ FIXED: Robust Close Logic
$(document).on('mousedown', function (e) {
    autocompleteIds.forEach(id => {
        const $input = $(`#${id}`);
        const $list = $(`#autocomplete-list-${id}`);

        // If the click is NOT the input AND NOT inside the autocomplete list
        if (!$input.is(e.target) && $list.has(e.target).length === 0) {
            $list.addClass('hidden');
        }
    });
});

function handledisplaylist(id, searchValue) {
    legacyAjax(1, { search: searchValue || "" }, {
        success: function (response) {
            const $list = $(`#autocomplete-list-${id}`);
            $list.empty();

            if (response.result === "success" && response.data.length > 0) {
                $.each(response.data, function (index, item) {
                    const $li = $(`
                        <li class="border-b last:border-b-0 border-gray-100">
                            <button type="button" class="w-full text-left p-3 cursor-pointer hover:bg-brand-50 transition-colors">
                                <span class="flex flex-col">
                                    <span class="font-semibold text-gray-800 text-sm">${escapeHtml(item.email)}</span>
                                    <span class="text-[10px] text-gray-400 uppercase font-bold">${escapeHtml(item.permissionLevel)}</span>
                                </span>
                            </button>
                        </li>
                    `);

                    // ✅ FIXED: Stop propagation so the document click doesn't hide it instantly
                    $li.on('click', function (e) {
                        e.stopPropagation();
                        
                        const $container = $(`#${id}Container`);
                        
                        // Prevent duplicates
                        if (tagExists($container, item.userId)) {
                            $list.addClass('hidden');
                            return;
                        }

                        // Create Tag
                        const $tag = $(userTagHtml(item, id, false));

                        $tag.find('.remove-user-tag').on('click', function () {
                            $tag.remove();
                        });

                        $container.append($tag);
                        $list.addClass('hidden').empty();
                        $(`#${id}`).val('');
                    });

                    $list.append($li);
                });
                $list.removeClass('hidden');
            } else {
                $list.addClass('hidden');
            }
        },
        error: function (xhr) {
            $(`#autocomplete-list-${id}`).addClass('hidden');
            if (xhr && (xhr.status === 401 || xhr.status === 419)) return;
            Toast.fire({ icon: 'error', title: 'Could not search users' });
        }
    });
}

$(document).ready(function() {
    $('.filter-btn').on('click', function() {
        // 1. Get the filter value from the clicked button
        const filterValue = $(this).data('filter');
        
        // 2. Update your global variable used in fetchData
        // (Assuming columnFilter is your global variable)
        columnFilter = filterValue; 
        currentPage = 1; // Reset to page 1 when changing filters

        // 3. UI: Update Button Styles
        // Reset all buttons to "In-active" (Transparent/Gray)
        $('.filter-btn').removeClass('bg-brand-500 text-white border-brand-500')
                        .addClass('bg-transparent text-[#5D6778] border-[#5D6778]');

        // Set clicked button to "Active" (Blue)
        $(this).removeClass('bg-transparent text-[#5D6778] border-[#5D6778]')
               .addClass('bg-brand-500 text-white border-brand-500');
        $('.filter-btn').attr('aria-pressed', 'false');
        $(this).attr('aria-pressed', 'true');

        // 4. Trigger the fetch
        fetchData();
    });

});

let fetchSeq = 0;
function showTableLoading() {
    leadsTableBody.html('<tr><td colspan="13" class="px-6 py-12 text-center text-sm text-gray-400"><svg class="inline w-5 h-5 mr-2 animate-spin text-brand-500" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>Loading test plans...</td></tr>');
    $('#table-scroll-container').attr('aria-busy', 'true');
}

function showTableError() {
    leadsTableBody.html('<tr><td colspan="13" class="px-6 py-12 text-center"><p class="text-sm font-semibold text-red-600">Could not load test plans.</p><button type="button" class="js-retry-fetch mt-3 px-4 py-2 text-sm font-semibold bg-brand-600 hover:bg-brand-700 text-white rounded-lg">Retry</button></td></tr>');
    $('#table-info').text('');
    paginationContainer.empty();
}

$(document).on('click', '.js-retry-fetch', function () { fetchData(); });

function fetchData() {
    const mySeq = ++fetchSeq;
    showTableLoading();

    legacyAjax(3, {
        searchKey: (typeof columnFilter !== 'undefined' && columnFilter) ? columnFilter : "all",
        searchQuery: searchText,
        currentPage: currentPage,
        categoryFilter: categoryFilter
    }, {
        success: (response) => {
            if (mySeq !== fetchSeq) return;
            $('#table-scroll-container').attr('aria-busy', 'false');
            handleAjaxResponse(response);
        },
        error: function (xhr) {
            if (mySeq !== fetchSeq) return;
            $('#table-scroll-container').attr('aria-busy', 'false');
            showTableError();
            if (xhr && (xhr.status === 401 || xhr.status === 419)) return; // handled by the global hook
            Toast.fire({ icon: 'error', title: 'Could not load test plans' });
        }
    });
}

    const rowsPerPage = 10;
    let currentPage = 1;

    // Cache DOM elements
    const leadsTableBody = $('#leadsTable tbody');
    const searchInput = $('#searchInput');
    // const columnFilter = $('#columnFilter');
    const paginationContainer = $('.pagination-buttons');
    const textInfo = $('#table-info');

    // Format Unix timestamp to date string
    function formatCreatedDate(unixTimestamp) {
        const date = new Date(unixTimestamp * 1000);
        const day = date.getDate();
        const month = date.getMonth() + 1;
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }


/**
 * 1. UI Helper: Renders user badges with a modern "pill" design and progress bar
 */

const SHOW_MORE_LIMIT = 1; // Number of users visible before "Show More"

const renderUsers = (arr, uniqueKey) => {
    if (!arr || !arr.length) return '<span class="text-gray-400 text-xs">Unassigned</span>';

    // Sort: MyUserId first
    const sorted = [...arr].sort((a, b) => {
        const aIsMe = (a.idUser === MyUserId || a.userEmail === MyUserId) ? -1 : 0;
        const bIsMe = (b.idUser === MyUserId || b.userEmail === MyUserId) ? -1 : 0;
        return aIsMe - bIsMe;
    });

    const cards = sorted.map((a, idx) => {
        const level = parseInt(a.level) || 0;
        const statusStr = (a.whatStatus || 'pending').toLowerCase();
        let colorConfig = {};

        // ✅ Logic for "Go Back" (Red Override)
        if (statusStr === 'go back') {
            colorConfig = { 
                text: 'text-red-700', 
                bar: 'bg-red-500', 
                bg: 'bg-red-50', 
                status: 'text-red-600' 
            };
        } 
        // ✅ Standard Level-based Logic
        else if (level === 0) {
            colorConfig = { 
                text: 'text-gray-700', 
                bar: 'bg-gray-400', 
                bg: 'bg-gray-100', 
                status: 'text-gray-400' 
            };
        } else if (level < 100) {
            colorConfig = { 
                text: 'text-orange-700', 
                bar: 'bg-orange-500', 
                bg: 'bg-orange-50', 
                status: 'text-orange-400' 
            };
        } else {
            colorConfig = { 
                text: 'text-green-700', 
                bar: 'bg-green-500', 
                bg: 'bg-green-50', 
                status: 'text-green-500' 
            };
        }

        const isMe = (a.idUser === MyUserId || a.userEmail === MyUserId);
        const emailTxt = a.userEmail || a.idUser || '';
        const userJson = escAttr(JSON.stringify(a));

        return `
        <div class="mb-3 last:mb-0 relative group ${idx >= SHOW_MORE_LIMIT ? 'user-overflow-item hidden' : ''}" ${idx >= SHOW_MORE_LIMIT ? `data-overflow-group="${escAttr(uniqueKey)}"` : ''}>
            <button type="button" data-user="${userJson}" aria-label="Remove ${escAttr(emailTxt)}"
                 class="js-remove-user absolute -top-1 -right-1 z-10 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center cursor-pointer hover:bg-red-700 transition-colors shadow-sm opacity-0 group-hover:opacity-100 focus:opacity-100 focus-visible:ring-2 focus-visible:ring-red-500"
                 title="Remove User">
                <span aria-hidden="true" style="font-size: 10px; font-weight: bold; margin-top:-1px;">×</span>
            </button>

            <button type="button" data-user="${userJson}"
                 class="js-open-user block w-full text-left cursor-pointer p-2 rounded-lg border ${isMe ? 'border-brand-300 ring-1 ring-brand-100' : 'border-transparent'} ${colorConfig.bg} hover:border-gray-300 hover:shadow-md transition-all active:scale-95">

                ${isMe ? '<span class="inline-block text-[8px] font-black text-brand-600 bg-brand-100 px-1.5 py-0.5 rounded-md uppercase tracking-widest mb-1">My Account</span>' : ''}

                <span class="flex justify-between items-center gap-3 mb-1.5">
                    <span class="font-bold ${colorConfig.text} text-[11px] truncate flex-1 min-w-0" title="${escAttr(emailTxt)}">
                        ${escapeHtml(emailTxt)}
                    </span>
                    <span class="text-[9px] font-black uppercase ${colorConfig.status} whitespace-nowrap tracking-tighter">
                        ${escapeHtml(a.whatStatus || 'pending')}
                    </span>
                </span>

                <span class="block w-full bg-gray-200/50 rounded-full h-1.5 overflow-hidden">
                    <span class="${colorConfig.bar} block h-full transition-all duration-700 shadow-sm"
                         style="width: ${level}%">
                    </span>
                </span>
            </button>
        </div>`;
    });

    let html = cards.join('');

    // Add Show More / Show Less toggle if overflow exists
    if (sorted.length > SHOW_MORE_LIMIT) {
        const extraCount = sorted.length - SHOW_MORE_LIMIT;
        html += `
        <button type="button" class="show-more-btn w-full mt-1 flex items-center justify-center gap-1 text-[10px] font-bold text-gray-500 hover:text-brand-600 transition-colors bg-gray-50 hover:bg-brand-50 px-2 py-1 rounded-lg border border-gray-200 hover:border-brand-200"
                data-group="${escAttr(uniqueKey)}">
            <svg class="w-3 h-3 show-more-arrow transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            <span class="show-more-label">Show ${extraCount} More</span>
        </button>`;
    }

    return html;
};

// Delegated handlers (data attributes instead of inline JSON in onclick)
function parseUserAttr(el) {
    try { return JSON.parse(el.getAttribute('data-user')); } catch (e) { return null; }
}
$(document).on('click', '.js-remove-user', function (e) {
    const u = parseUserAttr(this);
    if (u) confirmRemoveUser(e, u);
});
$(document).on('click', '.js-open-user', function () {
    const u = parseUserAttr(this);
    if (u) openUserUpdateModal(u);
});
$(document).on('click', '.show-more-btn', function () { toggleUserOverflow(this, this.getAttribute('data-group')); });
$(document).on('click', '.js-add-user', function () {
    openQuickAddModal(this.getAttribute('data-role'), parseInt(this.getAttribute('data-id'), 10));
});
$(document).on('click', '.bin-icon', function (e) { handleBinClick(e, this); });
$(document).on('click', '.action-icon', function (e) { handleactionClick(e, this); });

/** Toggle Show More / Show Less for user lists in table cells */
function toggleUserOverflow(btn, groupKey) {
    const items = document.querySelectorAll('[data-overflow-group="' + String(groupKey).replace(/["\\]/g, '') + '"]');
    const isHidden = items[0]?.classList.contains('hidden');
    const label = btn.querySelector('.show-more-label');
    const arrow = btn.querySelector('.show-more-arrow');

    items.forEach(el => {
        if (isHidden) {
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    });

    if (isHidden) {
        label.textContent = 'Show Less';
        arrow.style.transform = 'rotate(180deg)';
    } else {
        label.textContent = `Show ${items.length} More`;
        arrow.style.transform = '';
    }
}

/** Toggle Tracker Header Section — hides header & expands table */
let trackerCollapsed = false;
function toggleTrackerSection() {
    const header = document.getElementById('tracker-header-section');
    const table  = document.getElementById('table-scroll-container');
    const label  = document.getElementById('toggle-tracker-label');
    const icon   = document.getElementById('toggle-tracker-icon');

    trackerCollapsed = !trackerCollapsed;

    if (trackerCollapsed) {
        // Hide header with smooth collapse
        header.style.maxHeight = header.scrollHeight + 'px';
        header.offsetHeight; // force reflow
        header.style.overflow = 'hidden';
        header.style.maxHeight = '0px';
        header.style.padding = '0 24px';
        header.style.marginBottom = '0';
        header.style.opacity = '0';
        header.style.borderWidth = '0';

        // Expand table to fill the space
        table.style.maxHeight = 'calc(100vh - 220px)';

        label.textContent = 'Remove Focus';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 3h6m0 0v6m0-6l-7 7M9 21H3m0 0v-6m0 6l7-7"/>';
    } else {
        // Show header
        header.style.maxHeight = header.scrollHeight + 500 + 'px';
        header.style.padding = '';
        header.style.marginBottom = '';
        header.style.opacity = '1';
        header.style.borderWidth = '';
        setTimeout(() => { header.style.maxHeight = ''; header.style.overflow = ''; }, 350);

        // Restore table height
        table.style.maxHeight = '';

        label.textContent = 'Focus Table';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>';
    }
}

/**
 * 2. Logic: The Display Function (Now focused only on Table Generation)
 */
function displayPage() {
    leadsTableBody.empty();
    const fragment = document.createDocumentFragment();
    const pageData = filteredData.slice(0, 10);

    if (pageData.length === 0) {
        leadsTableBody.html('<tr><td colspan="13" class="px-6 py-14 text-center"><p class="text-sm font-semibold text-gray-600">No test plans found</p><p class="text-xs text-gray-400 mt-1">Try changing the filters or search term, or create a new request.</p></td></tr>');
        textInfo.text('');
        paginationContainer.empty();
        return;
    }

    pageData.forEach(item => {
        const roleMap = { asignToDeVId: [], generecTesterId: [], sandboxTesterId: [], alphaTesterId: [], betaTesterId: [], liveTesterId: [] };
        (item.assignments || []).forEach(a => { if (roleMap[a.whichPart]) roleMap[a.whichPart].push(a); });
     const formattedDate = formatCreatedDate(item.created);


// --- Status Logic ---
        const status = item.mainstatus || 'N/A';
        const isCompleted = status === 'Completed';
        const statusClasses = isCompleted 
            ? "bg-green-50 text-green-700 border border-green-100 shadow-sm" 
            : "bg-gray-100 text-gray-600 border border-transparent";
const renderDescription = (desc, files, title, caseInfo, category, typeOfStatus, priority) => {
    // 1. Clean HTML from short description safely
    const doc = new DOMParser().parseFromString(desc || '', 'text/html');
    const plainText = doc.body.textContent || "";
    
    const words = plainText.trim().split(/\s+/).filter(w => w);
    const isLong = words.length > 12;
    const shortDesc = isLong ? words.slice(0, 12).join(' ') + '...' : plainText;

    // 2. Sanitize and escape data for the onclick attribute to prevent breaking the DOM
    // We base64 encode or escape quotes to ensure HTML inside 'desc' doesn't break the template string
    const safeData = {
        desc: desc || '',
        files: files || [],
        title: title || 'No Title',
        caseInfo: caseInfo || '',
        category: category || 'General',
        typeOfStatus: typeOfStatus || '',
        priority: !!priority
    };

    // Use a data attribute instead of passing many args to avoid quote nesting issues
    const dataPayload = btoa(unescape(encodeURIComponent(JSON.stringify(safeData))));

    // 3. UI logic
    const priorityBadge = priority 
        ? `<span class="text-[10px] font-bold bg-red-50 text-red-600 px-2 py-0.5 rounded-md border border-red-100 uppercase flex items-center gap-1">
            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span> Priority
           </span>` 
        : '';

    const typeBadge = typeOfStatus
        ? `<span class="text-[10px] font-bold bg-purple-50 text-purple-600 px-2 py-0.5 rounded-md border border-purple-100 uppercase">${escapeHtml(typeOfStatus)}</span>` 
        : '<span class="text-[10px] text-gray-400 italic">Not set</span>';

    return `
        <div class="flex flex-col gap-3 max-w-[280px] py-2">
            <!-- Metadata Grid -->
            <div class="flex flex-col gap-2.5">
                ${priority ? `
                <div class="flex items-center gap-3">
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider w-[60px] shrink-0">Level</span>
                    ${priorityBadge}
                </div>
                ` : ''}

                <div class="flex items-center gap-3">
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider w-[60px] shrink-0">Category</span>
                    <span class="text-[10px] font-bold bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-md border border-indigo-100 uppercase">
                        ${escapeHtml(category || 'General')}
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider w-[60px] shrink-0">Type</span>
                    ${typeBadge}
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider w-[60px] shrink-0">Case</span>
                    <span class="text-[10px] font-medium text-brand-700 bg-brand-50/50 px-2 py-0.5 rounded border border-brand-100/50 truncate max-w-[160px]">
                        ${escapeHtml(caseInfo || 'N/A')}
                    </span>
                </div>
            </div>

            <!-- Description Preview -->
            <div class="mt-1 space-y-2">
                <div class="text-[11px] text-gray-600 leading-relaxed line-clamp-2 pl-3 border-l-2 border-slate-200 py-0.5 italic">
                    "${escapeHtml(shortDesc || 'No description provided.')}"
                </div>
                
                <button 
                    data-payload="${dataPayload}"
                    onclick="openDescriptionModal(this)"
                    class="flex items-center gap-2 mt-2 px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-200 hover:border-brand-400 rounded-lg transition-all group shadow-sm active:scale-95"
                >
                    <svg class="w-3.5 h-3.5 text-brand-500 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span class="text-[10px] font-black text-brand-600 uppercase tracking-widest">Full Details</span>
                </button>
            </div>
        </div>
    `;
};
        const tdContent = (roleId, users) => {
            const uniqueKey = `${roleId}_${item.id}`;
            return `
            <div class="flex flex-col gap-2">
                <button type="button" data-role="${escAttr(roleId)}" data-id="${escAttr(item.id)}" aria-label="Add user"
                        class="js-add-user w-fit flex items-center gap-1 text-[10px] font-bold text-brand-500 hover:text-brand-700 transition-colors bg-brand-50 px-2 py-0.5 rounded-full border border-brand-100">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-width="3" stroke-linecap="round"/></svg>
                    Add
                </button>
                <div class="flex flex-wrap gap-1">${renderUsers(users, uniqueKey)}</div>
            </div>
        `;};


        // Encode the entire item object to prevent HTML/Quote breakage
    const itemPayload = btoa(unescape(encodeURIComponent(JSON.stringify(item))));

        const row = document.createElement('tr'); 
        row.className = "hover:bg-gray-50 transition-colors border-b border-gray-100";
        row.innerHTML = `
            <td class="px-6 py-4 text-sm text-gray-500">${escapeHtml(item.creatorEmail || '-')}</td>
         <td class="px-6 py-4 text-sm text-gray-800">
    <div class="max-w-[150px] truncate font-semibold" title="${escAttr(item.title || '')}">
        ${escapeHtml(item.title || '-')}
    </div>
</td>
     
<td class="px-6 py-4">
    ${renderDescription(item.description, item.itemDataFile, item.title, item.caseInformation, (item.category && item.category.name) || item.categoryType, item.typeOfStatus, item.priority)}
</td>
            <td class="px-6 py-4"><span class="px-2 py-1 bg-gray-100 rounded text-[10px] font-mono text-gray-600">${escapeHtml(item.versionType)} v${escapeHtml(item.versionNumber)}</span></td>
       <td class="px-6 py-4">
                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider ${statusClasses}">
                    ${escapeHtml(status)}
                </span>
            </td>
            
            <td class="px-6 py-4">${tdContent('asignToDeVId', roleMap.asignToDeVId)}</td>
            <td class="px-6 py-4">${tdContent('generecTesterId', roleMap.generecTesterId)}</td>
            <td class="px-6 py-4">${tdContent('sandboxTesterId', roleMap.sandboxTesterId)}</td>
            <td class="px-6 py-4">${tdContent('alphaTesterId', roleMap.alphaTesterId)}</td>
            <td class="px-6 py-4">${tdContent('betaTesterId', roleMap.betaTesterId)}</td>
            <td class="px-6 py-4">${tdContent('liveTesterId', roleMap.liveTesterId)}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">${formattedDate|| '-'}</td>
            <td class="px-6 py-4 text-right">
                <div class="flex justify-end space-x-3">
         <button type="button" data-payload="${itemPayload}" title="Delete" aria-label="Delete test plan"
                     class="bin-icon p-1 rounded text-gray-400 hover:text-red-500 transition-colors">
                     <svg class="w-5 h-5 hover:scale-125 transition-transform" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                     </svg>
                 </button>
        <button type="button" data-payload="${itemPayload}" title="Edit" aria-label="Edit test plan"
                     class="action-icon p-1 rounded text-gray-400 hover:text-brand-600 transition-colors">
                     <svg class="w-5 h-5 hover:scale-125 transition-transform" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                     </svg>
                 </button>
                </div>
            </td>
        `;
        fragment.appendChild(row);
    });

    leadsTableBody.append(fragment);

    // const totalEntries = filteredData.length;
    // const startIndex = 1;
    // const endIndex = Math.min(10, totalEntries);
    const rowsPerPage = 10;
    const startIdx = (currentPage - 1) * rowsPerPage;
    
    
     const showStart = totalEntries > 0 ? startIdx + 1 : 0;
       currententity = currentPage * totalpagesshown - totalpagesshown + 1;

    textInfo.text(`Showing ${currententity} to ${endIndex} of ${totalEntries} entries`);
    generatePaginationControls(totalEntries);
}





$(document).ready(function() {
    
    /**
     * Handle Version Type Change in Main Form
     * Fetches versions from backend and populates version number field
     */
    $('#versionType').on('change', function() {
        const selectedType = $(this).val();
        
        // Don't proceed if no type selected
        if (!selectedType) {
            $('#versionNumber').val('').prop('disabled', false);
            return;
        }
        
        // Show loading state
        $('#versionNumber')
            .prop('disabled', true)
            .val('Loading versions...')
            .css('opacity', '0.5');
        
        // Make AJAX call to fetch versions
        fetchVersionsAndPopulate(selectedType, '#versionNumber', false);
    });
    
    /**
     * Handle Version Type Change in Edit Modal
     * Fetches versions from backend and populates version number field
     */
    $(document).on('change', '#edit-versionType', function() {
        const selectedType = $(this).val();
        
        // Don't proceed if no type selected
        if (!selectedType) {
            $('#edit-versionNumber').val('').prop('disabled', false);
            return;
        }
        
        // Show loading state
        $('#edit-versionNumber')
            .prop('disabled', true)
            .val('Loading versions...')
            .css('opacity', '0.5');
        
        // Make AJAX call to fetch versions
        fetchVersionsAndPopulate(selectedType, '#edit-versionNumber', true);
    });
    
    /**
     * Fetch Versions from Backend and Populate Select/Input
     * 
 
     */
    function fetchVersionsAndPopulate(versionType, targetInputId, isEditModal) {
        // Show loading only if request takes longer than 1.5 seconds
        let loadingTriggered = false;
        const loadingTimer = setTimeout(() => {
            loadingTriggered = true;
            Swal.fire({
                title: 'Processing...',
                html: 'This is taking a bit longer than usual, please wait.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        }, 1500);

        legacyAjax(100, { versionType: versionType }, {
            success: function(response) {
                clearTimeout(loadingTimer);
                if (loadingTriggered) Swal.close();
                if (response.result === 'success' && response.data) {
                    // Create dropdown options from fetched versions
                    const versions = response.data;
                    
                    if (versions.length > 0) {
                        // Create a dropdown or populate input based on context
                        populateVersionField(targetInputId, versions);
                    } else {
                        // No versions available
                        showVersionError(targetInputId, 'No versions available for this type');
                    }
                } else {
                    // Error in response
                    showVersionError(
                        targetInputId,
                        response.message || 'Error fetching versions'
                    );
                }
            },
            error: function(xhr, status, error) {
                clearTimeout(loadingTimer);
                if (loadingTriggered) Swal.close();
                // Network or server error
                showVersionError(
                    targetInputId,
                    'Failed to fetch versions. Please try again.'
                );
            }
        });
    }
    
    /**
     * Populate the version field with fetched versions
     * Creates a dropdown menu for version selection
     * 

     */
    function populateVersionField(targetInputId, versions) {
        const $targetField = $(targetInputId);
        
        // Clear previous state
        $targetField.css('opacity', '1').prop('disabled', false);
        
        // Create dropdown HTML
        let optionsHtml = '<option value="">Select a version</option>';
        versions.forEach(version => {
            optionsHtml += `<option value="${escAttr(version)}">${escapeHtml(version)}</option>`;
        });
        
        // Replace input with select dropdown
        const $newSelect = $(`
            <select id="${targetInputId.substring(1)}" name="versionNumber" aria-label="Version number" 
                class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 outline-none transition-all">
                ${optionsHtml}
            </select>
        `);
        
        // Replace or update field
        $targetField.replaceWith($newSelect);
        
        // Add change handler for the new dropdown
        $newSelect.on('change', function() {
            if ($(this).val()) {
                $(this).addClass('border-brand-500 bg-brand-50');
            } else {
                $(this).removeClass('border-brand-500 bg-brand-50');
            }
        });
        
        // Show success feedback
        showVersionSuccess(targetInputId);
    }
    
    /**
     * Show error message for version fetch
     * 

     */
    function showVersionError(targetInputId, message) {
        const $field = $(targetInputId);
        
        $field
            .val('Error: ' + message)
            .css('opacity', '1')
            .prop('disabled', true)
            .css('border-color', '#ef4444')
            .css('background-color', '#fee2e2');
        
        // Show Swal alert
        Swal.fire({
            icon: 'error',
            title: 'Version Fetch Failed',
            text: message,
            confirmButtonColor: '#ef4444',
            customClass: { popup: 'rounded-2xl' }
        });
        
        // Reset after 3 seconds
        setTimeout(() => {
            $field
                .val('')
                .css('opacity', '1')
                .prop('disabled', false)
                .css('border-color', '#d1d5db')
                .css('background-color', '#f3f4f6');
        }, 3000);
    }
    
    /**
     * Show success feedback after versions loaded
     * 
 
     */
    function showVersionSuccess(targetInputId) {
        const $field = $(targetInputId);
        
        // Add brief success styling
        $field.css('border-color', '#10b981').css('background-color', '#ecfdf5');
        
        // Remove after 1 second
        setTimeout(() => {
            $field
                .css('border-color', '#d1d5db')
                .css('background-color', '#f3f4f6');
        }, 1000);
    }
});





async function handleactionClick(event, element) {
    if (event && event.stopPropagation) event.stopPropagation();

    // 1. Decode the item data
    let item;
    try {
        const payload = element.getAttribute('data-payload');
        item = JSON.parse(decodeURIComponent(escape(atob(payload))));
    } catch (e) {
        Toast.fire({ icon: "error", title: "Could not open editor" });
        return;
    }


    let existingFiles = [];
    try {
        existingFiles = typeof item.itemDataFile === 'string' ? JSON.parse(item.itemDataFile) : (item.itemDataFile || []);
    } catch (e) { existingFiles = []; }

    Swal.fire({
        width: 'min(50rem, 95vw)',
        showCancelButton: true,
        confirmButtonText: 'Save Changes',
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        customClass: {
            popup: 'rounded-3xl shadow-2xl',
            confirmButton: 'bg-brand-600 hover:bg-brand-700 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-brand-100',
            cancelButton: 'bg-gray-100 hover:bg-gray-200 text-gray-600 px-8 py-3 rounded-xl font-bold transition-all'
        },
        html: `
                <div class="text-left">
                <span class="text-[10px] font-black text-brand-500 uppercase tracking-widest">Editor Mode</span>
                <h3 class="text-xl font-bold text-gray-800">Update Test Plan</h3>
            </div>
            <div class="text-left space-y-5 mt-4 font-sans text-sm">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 sm:col-span-8" id="edit-wrap-title">
                        <label for="edit-title" class="text-[10px] font-black text-gray-400 uppercase block mb-1.5 ml-1">Title <span class="text-red-400">*</span></label>
                        <input type="text" id="edit-title" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-50 transition-all" value="${escAttr(item.title)}">
                    </div>
                    <div class="col-span-12 sm:col-span-4">
                        <label for="edit-mainstatus" class="text-[10px] font-black text-gray-400 uppercase block mb-1.5 ml-1">Main Status</label>
                        <select id="edit-mainstatus" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-brand-400 transition-all">

                            <option value="Open" ${item.mainstatus === 'Open' ? 'selected' : ''}>Open</option>
                            <option value="Completed" ${item.mainstatus === 'Completed' ? 'selected' : ''}>Completed</option>
                        </select>
                    </div>
                </div>

           <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-6" id="edit-wrap-typeOfStatus">
                        <label for="edit-typeOfStatus" class="text-[10px] font-black text-gray-400 uppercase block mb-1.5 ml-1">Type of Status <span class="text-red-400">*</span></label>
                        <select id="edit-typeOfStatus" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-brand-400 transition-all">
                            <option value="">Select Type</option>
                            <option value="New" ${item.typeOfStatus === 'New' ? 'selected' : ''}>New</option>
                            <option value="Bug" ${item.typeOfStatus === 'Bug' ? 'selected' : ''}>Bug</option>
                        </select>
                    </div>
             <div class="col-span-6">
    <span class="text-[10px] font-black text-gray-400 uppercase block mb-1.5 ml-1">Priority</span>
    <div class="flex items-center h-[42px] px-3 bg-gray-50 border border-gray-200 rounded-xl">
        <label class="flex items-center gap-2 cursor-pointer w-full">
            <input type="checkbox" id="edit-priority" ${item.priority ? 'checked' : ''} 
                   class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
            <span class="text-sm font-semibold text-gray-700">Mark as Priority</span>
        </label>
    </div>
</div>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12" id="edit-wrap-categoryType">
                        <label for="editCategoryTypeToggle" class="text-[10px] font-black text-gray-400 uppercase block mb-1.5 ml-1">Category</label>
                        <div class="relative" id="editCategoryTypeWrapper">
                            <input type="hidden" id="edit-categoryTypeValue">
                            <button type="button" id="editCategoryTypeToggle" aria-haspopup="listbox" aria-expanded="false"
                                class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl cursor-pointer flex justify-between items-center text-left focus:border-brand-400 focus-visible:ring-2 focus-visible:ring-brand-500 transition-all">
                                <span id="editCategoryTypeLabel" class="text-gray-500 text-sm">Select Category</span>
                                <svg class="w-4 h-4 text-gray-400" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="editCategoryTypeDropdown" data-dropdown
                                class="absolute left-0 right-0 z-50 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl hidden custom-scrollbar">
                                <div class="sticky top-0 bg-white border-b border-gray-100 p-2">
                                    <input type="text" id="editCategoryTypeSearch" placeholder="Search categories..." aria-label="Search categories"
                                        class="w-full px-2 py-1.5 text-sm bg-gray-50 border border-gray-200 rounded outline-none focus:border-brand-400">
                                </div>
                                <div id="editCategoryTypeOptions" class="max-h-[160px] overflow-y-auto"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-6" id="edit-wrap-versionType">
                        <label for="edit-versionType" class="text-[10px] font-black text-gray-400 uppercase block mb-1.5 ml-1">Type <span class="text-red-400">*</span></label>
                        <select id="edit-versionType" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-brand-400 transition-all">
                        <option value="">Select Type</option>
                             <option value="sandbox" ${item.versionType === 'sandbox' ? 'selected' : ''}>SandBox</option>
                            <option value="alpha" ${item.versionType === 'alpha' ? 'selected' : ''}>Alpha</option>
                            <option value="beta" ${item.versionType === 'beta' ? 'selected' : ''}>Beta</option>
                            <option value="live" ${item.versionType === 'live' ? 'selected' : ''}>Live</option>
                        </select>
                    </div>
                    <div class="col-span-6">
                        <label for="edit-versionNumber" class="text-[10px] font-black text-gray-400 uppercase block mb-1.5 ml-1">Version No.</label>
                        <input type="text" id="edit-versionNumber" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none" value="${escAttr(item.versionNumber)}">
                    </div>
                </div>

                <div id="existing-files-container" class="${existingFiles.length === 0 ? 'hidden' : ''}">
                    <span class="text-[10px] font-black text-gray-400 uppercase block mb-2 ml-1">Current Attachments</span>
                    <div class="flex flex-wrap gap-2 p-3 bg-gray-50 rounded-2xl border border-gray-100" id="existing-files-list">
                        ${existingFiles.map(url => `
                            <div class="flex items-center gap-2 bg-white px-2 py-1.5 rounded-lg border border-gray-200 shadow-sm" data-url="${escAttr(url)}">
                                <span class="text-[11px] font-semibold text-gray-600 truncate max-w-[120px]">${escapeHtml(String(url).split('/').pop())}</span>
                                <button type="button" aria-label="Remove attachment" class="text-gray-300 hover:text-red-500 transition-colors" onclick="this.parentElement.remove()">×</button>
                            </div>
                        `).join('')}
                    </div>
                </div>

                <div>
                    <label for="edit-file" class="text-[10px] font-black text-gray-400 uppercase block mb-1.5 ml-1">Add New Files <span class="normal-case font-normal text-gray-400">(max ${MAX_FILES_PER_REQUEST} files, ${MAX_FILE_SIZE_MB} MB each)</span></label>
                    <input type="file" id="edit-file" accept="${FILE_ACCEPT}" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-brand-50 file:text-brand-600 border border-gray-200 rounded-2xl p-1.5 bg-white shadow-sm" multiple>
                </div>

                <div id="edit-wrap-caseInfo">
                    <label for="edit-caseInfo" class="text-[10px] font-black text-gray-400 uppercase block mb-1.5 ml-1">Case Information <span class="text-red-400">*</span></label>
                    <textarea id="edit-caseInfo" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none resize-none font-mono text-xs">${escapeHtml(item.caseInformation || '')}</textarea>
                </div>
                
                <div id="edit-wrap-description">
                    <span class="text-[10px] font-black text-gray-400 uppercase block mb-1.5 ml-1">Description <span class="text-red-400">*</span></span>
                    <div id="edit-description-toolbar">
                        <span class="ql-formats">
                            <button class="ql-bold"></button>
                            <button class="ql-italic"></button>
                            <button class="ql-underline"></button>
                            <button class="ql-strike"></button>
                        </span>
                        <span class="ql-formats">
                            <button class="ql-list" value="ordered"></button>
                            <button class="ql-list" value="bullet"></button>
                        </span>
                        <span class="ql-formats">
                            <button class="ql-link"></button>
                            <button class="ql-clean"></button>
                        </span>
                    </div>
                    <div id="edit-description-editor"></div>
                </div>
            </div>
        `,
        didOpen: () => {
            // Initialize Quill editor for edit modal
            window._editQuill = new Quill('#edit-description-editor', {
                modules: { toolbar: '#edit-description-toolbar' },
                theme: 'snow',
                placeholder: 'Enter description...'
            });
            // Load existing description HTML
            const existingDesc = item.description || '';
            window._editQuill.root.innerHTML = sanitizeHtml(existingDesc);

            // Populate the category dropdown with scroll-based pagination,
            // pre-selecting the current item's category.
            // If categories2 is empty (page loaded fresh),
            // fetch first then populate.
            if (categories2.length > 0) {
                populateCategoryDropdown('editCategoryType', item.categoryType || '');
            } else {
                handleCategoryAction2(104, { search: '' }).done(function(response) {
                    if (response.data) {
                        categories2 = response.data;
                        filteredData2 = [...categories2];
                    }
                    populateCategoryDropdown('editCategoryType', item.categoryType || '');
                });
            }
        },
        preConfirm: async () => {
            // --- Validation matching myForm style ---
            const editFields = {
                title:         { id: 'edit-title',         required: true,  label: 'Title',           type: 'input',  wrapId: 'edit-wrap-title' },
                description:   { id: 'edit-description-editor', required: true,  label: 'Description',     type: 'quill',  wrapId: 'edit-wrap-description' },
                caseInfo:      { id: 'edit-caseInfo',      required: true,  label: 'Case Information', type: 'input',  wrapId: 'edit-wrap-caseInfo' },
                typeOfStatus:  { id: 'edit-typeOfStatus',  required: true,  label: 'Type of Status',   type: 'input',  wrapId: 'edit-wrap-typeOfStatus' },
                versionType:   { id: 'edit-versionType',   required: true,  label: 'Version Type',     type: 'input',  wrapId: 'edit-wrap-versionType' },
            };

            let errors = [];

            // Clear previous validation state
            document.querySelectorAll('.swal-field-validation-error').forEach(el => el.classList.remove('swal-field-validation-error'));
            document.querySelectorAll('.swal-field-error-msg').forEach(el => el.remove());

            for (const [key, config] of Object.entries(editFields)) {
                let value;
                if (config.type === 'quill') {
                    if (window._editQuill) {
                        const html = window._editQuill.root.innerHTML;
                        value = (html === '<p><br></p>' || html.trim() === '') ? '' : html;
                    } else {
                        value = '';
                    }
                } else {
                    value = $(`#${config.id}`).val()?.trim();
                }

                if (config.required && !value) {
                    errors.push(config.label);
                    const wrapEl = document.getElementById(config.wrapId);
                    if (wrapEl) {
                        wrapEl.classList.add('swal-field-validation-error');
                        // Re-trigger animation
                        wrapEl.style.animation = 'none';
                        wrapEl.offsetHeight; // force reflow
                        wrapEl.style.animation = '';
                    }
                }
            }

            if (errors.length > 0) {
                Swal.showValidationMessage(errors.map(e => `${e} is required`).join(' · '));
                return false;
            }

            // Get description HTML from Quill
            const descriptionHtml = window._editQuill.root.innerHTML;
            // Treat empty Quill content as empty
            const cleanDesc = (descriptionHtml === '<p><br></p>' || descriptionHtml.trim() === '') ? '' : descriptionHtml;

            const remainingFiles = [];
            $('#existing-files-list div').each(function() {
                remainingFiles.push($(this).data('url'));
            });

            const toBase64 = file => new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = () => resolve(reader.result);
                reader.onerror = error => reject(error);
            });

            let newUrls = [];
            const fileInput = document.getElementById('edit-file');
            if (fileInput.files.length > 0) {
                const editFiles = Array.from(fileInput.files);
                if (editFiles.length > MAX_FILES_PER_REQUEST) {
                    Swal.showValidationMessage(`You can upload at most ${MAX_FILES_PER_REQUEST} files at a time.`);
                    return false;
                }
                const badType = editFiles.filter(f => !isAllowedFile(f));
                if (badType.length > 0) {
                    Swal.showValidationMessage(`File type not allowed: ${badType.map(f => escapeHtml(f.name)).join(', ')}`);
                    return false;
                }
                const oversized = editFiles.filter(f => f.size > MAX_FILE_SIZE_MB * 1024 * 1024);
                if (oversized.length > 0) {
                    Swal.showValidationMessage(`Files exceed ${MAX_FILE_SIZE_MB} MB limit: ${oversized.map(f => escapeHtml(f.name)).join(', ')}`);
                    return false;
                }
                // Upload to Laravel storage and merge the returned URLs with the kept files
                try {
                    newUrls = await uploadFiles(editFiles);
                } catch (upErr) {
                    Swal.showValidationMessage(escapeHtml(upErr.message || 'File upload failed.'));
                    return false;
                }
            }

            return {
                testplanId: item.id,
                title: $('#edit-title').val().trim(),
                 type: 8,
                mainstatus: $('#edit-mainstatus').val(),
                categoryType: $('#edit-categoryTypeValue').val(),
                typeOfStatus: $('#edit-typeOfStatus').val(),
                priority: $('#edit-priority').is(':checked'),
                caseInformation: $('#edit-caseInfo').val(),
                description: cleanDesc,
                versionType: $('#edit-versionType').val(),
                versionNumber: $('#edit-versionNumber').val(),
                existingFiles: [...remainingFiles, ...newUrls]
            };
        }
    }).then((result) => {
        if (result.isConfirmed) updateRecordWithFiles(result.value);
    });
}

function updateRecordWithFiles(payload) {
    // Show loading only if request takes longer than 1.5 seconds
    let loadingTriggered = false;
    const loadingTimer = setTimeout(() => {
        loadingTriggered = true;
        Swal.fire({
            title: 'Processing...',
            html: 'This is taking a bit longer than usual, please wait.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    }, 1500);

    legacyAjax(8, payload, {
        success: function(response) {
            clearTimeout(loadingTimer);
            if (loadingTriggered) Swal.close();
            if (response.result === "success") {
                Swal.fire({
                    icon: 'success',
                    title: 'Plan Updated',
                    toast: true, 
                    position: 'top-end', 
                    timer: 2500, 
                    showConfirmButton: false 
                });
                if (typeof fetchData === "function") fetchData();
            } else {
                Swal.fire({ icon: 'error', title: 'Update Failed', text: response.message || 'Error saving changes' });
            }
        },
        error: function(xhr) {
            clearTimeout(loadingTimer);
            if (loadingTriggered) Swal.close();
            if (xhr && (xhr.status === 401 || xhr.status === 419)) return;
            Swal.fire({ icon: 'error', title: 'Update Failed', text: apiMsg(xhr, 'Could not connect to server') });
        }
    });
}
/**
 * Modified openDescriptionModal to work with the data-payload approach.
 * This version decodes the base64 payload from the button and maps it to the
 * function parameters correctly, preventing HTML injection bugs.
 */
$(document).on('click', '.js-preview', function () { previewAttachment(this.getAttribute('data-url')); });

function openDescriptionModal(element) {
    let data;
    try {
        // Decode the payload from the data attribute
        const payload = element.getAttribute('data-payload');
        data = JSON.parse(decodeURIComponent(escape(atob(payload))));
    } catch (e) {
        Toast.fire({ icon: "error", title: "Could not open details" });
        return;
    }

    // Extract variables from payload
    const { 
        desc: fullDesc, 
        files, 
        title, 
        caseInfo, 
        category, 
        typeOfStatus, 
        priority 
    } = data;

    let fileArray = [];
    try {
        fileArray = typeof files === 'string' ? JSON.parse(files) : (files || []);
    } catch (e) { 
        fileArray = []; 
    }

    const filesHtml = fileArray.map((rawUrl) => {
        const url = safeUrl(rawUrl);
        if (!url) return '';
        const fileName = escapeHtml(url.split('?')[0].split('/').pop());
        const urlAttr = escAttr(url);
        return `
            <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-xl hover:bg-brand-50 hover:border-brand-200 transition-all group">
                <div class="flex items-center gap-3 overflow-hidden flex-1">
                    <div class="bg-brand-100 p-2 rounded-lg text-brand-600 shrink-0">
                        <svg class="w-4 h-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    </div>
                    <span class="text-xs font-bold text-gray-700 truncate max-w-[250px]">${fileName}</span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" data-url="${urlAttr}" aria-label="Preview ${fileName}" class="js-preview p-1.5 rounded-lg hover:bg-brand-100 text-gray-400 hover:text-brand-600 transition-all" title="View">
                        <svg class="w-4 h-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                    <a href="${urlAttr}" download target="_blank" rel="noopener noreferrer" aria-label="Download ${fileName}" class="p-1.5 rounded-lg hover:bg-brand-100 text-gray-400 hover:text-brand-600 transition-all" title="Download">
                        <svg class="w-4 h-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </a>
                </div>
            </div>
        `;
    }).join('');

    const priorityHtml = priority ? '<span class="inline-flex items-center px-3 py-1.5 rounded-lg text-[11px] font-bold bg-red-50 text-red-700 border border-red-100 shadow-sm"><span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-2"></span>⭐ Priority</span>' : '';
    const typeOfStatusHtml = typeOfStatus ? `<span class="inline-flex items-center px-3 py-1.5 rounded-lg text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-100 shadow-sm"><span class="w-1.5 h-1.5 rounded-full bg-purple-500 mr-2"></span>${escapeHtml(typeOfStatus)}</span>` : '';

    Swal.fire({
        width: 'min(62.5rem, 95vw)',
        padding: '1.25rem',
        showConfirmButton: false,
        showCloseButton: true,
        customClass: { 
            popup: 'rounded-3xl shadow-2xl',
            htmlContainer: 'text-left m-0' 
        },
        html: `
            <div class="text-left font-sans">
                <div class="mb-8 border-b border-gray-100 pb-6">
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.15em]">Category</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 shadow-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 mr-2"></span>
                                ${escapeHtml(category || 'General')}
                            </span>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.15em]">Type & Status</span>
                            <div class="flex gap-2">
                                ${typeOfStatusHtml || '<span class="text-[10px] text-gray-400 italic">Not set</span>'}
                                ${priorityHtml}
                            </div>
                        </div>
                        
                        <div>
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Plan Details</span>
                            <h3 class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight tracking-tight break-words">${escapeHtml(title || 'No Title')}</h3>
                        </div>
                    </div>
                </div>

                <div class="space-y-8">
                    <div class="w-full">
                        <div class="flex items-center gap-2 mb-2 ml-1">
                             <div class="w-1 h-3 bg-brand-500 rounded-full"></div>
                             <label class="text-[11px] font-black text-gray-500 uppercase tracking-widest">Case Information</label>
                        </div>
                        <div class="w-full p-5 bg-brand-50/30 rounded-2xl border border-brand-100/50 text-sm text-gray-700 leading-relaxed max-h-[200px] overflow-y-auto custom-scrollbar">
                            ${escapeHtml(caseInfo || 'No information provided.')}
                        </div>
                    </div>

                    <div class="w-full">
                        <div class="flex items-center gap-2 mb-2 ml-1">
                             <div class="w-1 h-3 bg-gray-400 rounded-full"></div>
                             <label class="text-[11px] font-black text-gray-500 uppercase tracking-widest">Description</label>
                        </div>
                        <div class="w-full bg-gray-50 rounded-2xl border border-gray-100 overflow-hidden shadow-inner">
                            <div id="desc-view-quill-container" class="p-5 text-gray-800 min-h-[100px] max-h-[400px] overflow-y-auto custom-scrollbar"></div>
                        </div>
                    </div>

                    ${fileArray.length > 0 ? `
                    <div class="w-full">
                        <div class="flex items-center gap-2 mb-3 ml-1">
                             <div class="w-1 h-3 bg-indigo-400 rounded-full"></div>
                             <label class="text-[11px] font-black text-gray-500 uppercase tracking-widest">Attachments (${fileArray.length})</label>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            ${filesHtml}
                        </div>
                    </div>
                    ` : ''}
                </div>
            </div>
        `,
        didOpen: () => {
            const descContainer = document.getElementById('desc-view-quill-container');
            if (descContainer && typeof Quill !== 'undefined') {
                const viewQuill = new Quill(descContainer, {
                    theme: 'snow',
                    readOnly: true,
                    modules: { toolbar: false }
                });
                viewQuill.root.innerHTML = fullDesc ? sanitizeHtml(fullDesc) : '<p class="text-gray-400 italic">No description provided.</p>';
                
                // Cleanup Quill default styles
                const editor = descContainer.querySelector('.ql-editor');
                if(editor) editor.style.padding = '0';
                descContainer.style.border = 'none';
                const toolbar = descContainer.parentElement.querySelector('.ql-toolbar');
                if(toolbar) toolbar.remove();
            } else if (descContainer) {
                // Fallback if Quill isn't loaded
                descContainer.innerHTML = fullDesc ? sanitizeHtml(fullDesc) : 'No description provided.';
            }
        }
    });
}
/**
 * 1. Search Modal with Confirmation Step
 */
function openQuickAddModal(roleId, mainItemId) {
    Swal.fire({
        title: '<span class="text-lg font-bold text-gray-800">Add Team Member</span>',
        html: `
            <div class="text-left font-sans mt-4 relative">
                <label for="quick-search-input" class="text-[11px] font-black text-gray-400 uppercase tracking-widest block mb-2">Search by Email</label>
                <div class="relative">
                    <input type="text" id="quick-search-input" 
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-brand-100 focus:border-brand-400 transition-all" 
                        placeholder="Type at least 2 characters to search..." autocomplete="off">
                    <ul id="quick-search-list" 
                        class="absolute left-0 right-0 z-[999] mt-2 bg-white border border-gray-200 rounded-xl shadow-2xl max-h-52 overflow-y-auto hidden">
                    </ul>
                </div>
                <div class="mt-4 p-3 bg-brand-50 rounded-lg text-[11px] text-brand-600 font-bold uppercase tracking-wide">
                    Add another member
                </div>
            </div>
        `,
        showConfirmButton: false,
        showCloseButton: true,
        width: 'min(32rem, 95vw)',
        customClass: { popup: 'rounded-2xl shadow-2xl' },
        didOpen: () => {
            const input = document.getElementById('quick-search-input');
            const list = document.getElementById('quick-search-list');

            const showHint = (msg) => {
                list.innerHTML = '';
                const hint = document.createElement('li');
                hint.className = 'p-3 text-xs text-gray-400 text-center';
                hint.textContent = msg;
                list.appendChild(hint);
                list.classList.remove('hidden');
            };

            const performSearch = (val) => {
                val = (val || '').trim();
                if (val.length < 2) { showHint('Type at least 2 characters to search'); return; }
                legacyAjax(1, { search: val }, {
                    error: function (xhr) {
                        if (xhr && (xhr.status === 401 || xhr.status === 419)) return;
                        showHint(apiMsg(xhr, 'Could not search users'));
                    },
                    success: function (response) {
                        list.innerHTML = '';
                        if (response.result === "success" && response.data.length > 0) {
                            response.data.forEach(item => {
                                const li = document.createElement('li');
                                li.className = "p-3 cursor-pointer hover:bg-brand-50 border-b last:border-b-0 border-gray-100 transition-colors flex flex-col";
                                li.tabIndex = 0;
                                li.setAttribute('role', 'option');
                                const emailSpan = document.createElement('span');
                                emailSpan.className = 'font-semibold text-gray-800 text-sm';
                                emailSpan.textContent = item.email;
                                const permSpan = document.createElement('span');
                                permSpan.className = 'text-[10px] text-gray-400 uppercase font-bold';
                                permSpan.textContent = item.permissionLevel;
                                li.appendChild(emailSpan);
                                li.appendChild(permSpan);
                                li.addEventListener('keydown', (ke) => { if (ke.key === 'Enter' || ke.key === ' ') { ke.preventDefault(); li.click(); } });

                                // --- MODIFIED SECTION: Add Confirmation ---
li.onclick = () => {
    const selectedEmail = item.email;
    const permission = escapeHtml(item.permissionLevel);
    const initial = escapeHtml(String(selectedEmail || '?').charAt(0).toUpperCase());
    
    // Format roleId (e.g., 'asignToDeVId' -> 'Assign To De V')
    const roleName = roleId.replace('Id', '').replace(/([A-Z])/g, ' $1').trim();

    Swal.fire({
        title: null, // Custom title in HTML for better control
        html: `
            <div class="text-left font-sans">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-brand-600 rounded-2xl flex items-center justify-center text-white text-xl font-black shadow-lg shadow-brand-100">
                        ${initial}
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-gray-800 leading-tight">Confirm Assignment</h3>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">Team Management</p>
                    </div>
                </div>

                <p class="text-sm text-gray-500 mb-5 leading-relaxed">
                    You are granting <span class="font-bold text-gray-800">${escapeHtml(selectedEmail)}</span> access to this project.
                </p>
                
                <div class="bg-gray-50 rounded-2xl border border-gray-100 overflow-hidden">
                    <div class="p-4 flex flex-col gap-4">
                    

                        <div class="h-px bg-gray-200/50 w-full"></div>

                        <div class="flex justify-between items-center">
                            <div class="flex flex-col">
                                <span class="text-[9px] font-black uppercase tracking-widest text-gray-400">System Permission</span>
                                <span class="text-sm font-bold text-gray-600">${permission}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-bold text-gray-400 italic"></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-5 flex items-start gap-3 p-3 bg-brand-50/50 rounded-xl border border-brand-100/50">
                    <div class="mt-0.5">
                        <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-[11px] leading-relaxed text-brand-700/80 font-medium">
                        The user will be added to this task .
                    </p>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Confirm Assignment',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0083bb', // Modern Blue
        cancelButtonColor: '#ffffff',
        reverseButtons: true,
        buttonsStyling: false, // Turn off default to use Tailwind
        customClass: {
            popup: 'rounded-[32px] p-8 shadow-2xl border border-gray-100',
            confirmButton: 'bg-brand-600 hover:bg-brand-700 text-white px-8 py-3.5 rounded-2xl text-sm font-bold transition-all shadow-lg shadow-brand-100 ml-3',
            cancelButton: 'text-gray-400 hover:text-gray-600 px-8 py-3.5 text-sm font-bold transition-colors'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            addNewAssignment(mainItemId, roleId, item.userId);
        } else {
            openQuickAddModal(roleId, mainItemId);
        }
    });
};                         // --- END MODIFIED SECTION ---

                                list.appendChild(li);
                            });
                            list.classList.remove('hidden');
                        } else {
                            list.classList.add('hidden');
                        }
                    }
                });
            };

            let quickTimer;
            input.addEventListener('focus', function() { performSearch(this.value); });
            input.addEventListener('keyup', function() { clearTimeout(quickTimer); const v = this.value; quickTimer = setTimeout(() => performSearch(v), 250); });

            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !list.contains(e.target)) {
                    list.classList.add('hidden');
                }
            });
        }
    });
}

/**
 * 2. Logic: The API call to save the assignment
 */
let assigningNow = false;
function addNewAssignment(mainId, roleType, selectedUserId) {
    if (assigningNow) return;
    assigningNow = true;
    const roleMap = {
        'asignToDeVId': 1,
        'generecTesterId': 2,
        'sandboxTesterId': 3,
        'alphaTesterId': 4,
        'betaTesterId': 5,
        'liveTesterId': 6
    };

    const numericRoleId = roleMap[roleType] || 0;

    // Show loading only if request takes longer than 1.5 seconds
    let loadingTriggered = false;
    const loadingTimer = setTimeout(() => {
        loadingTriggered = true;
        Swal.fire({
            title: 'Processing...',
            html: 'This is taking a bit longer than usual, please wait.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    }, 1500);

    legacyJson(6, {
        assignId: mainId,
        idAssign: numericRoleId,
        userId: selectedUserId
    })
    .then(data => {
        assigningNow = false;
        clearTimeout(loadingTimer);
        if (loadingTriggered) Swal.close();
        if(data.result === 'success') {
            Swal.fire({ 
                icon: 'success', 
                title: 'User Assigned', 
                toast: true, 
                position: 'top-end', 
                showConfirmButton: false, 
                timer: 2000 
            });
            if (typeof fetchData === "function") fetchData(); 
        } else {
            Swal.fire({ icon: 'error', title: 'Failed to add user', text: data.message || 'Could not add the user.' });
        }
    })
    .catch(err => {
        assigningNow = false;
        clearTimeout(loadingTimer);
        if (loadingTriggered) Swal.close();
        Swal.fire({ icon: 'error', title: 'Failed to add user', text: 'Something went wrong. Please try again.' });
    });
}


function openUserUpdateModal(user) {
    let selectedFiles2 = [];
    let existingFiles = [];

    try {
        existingFiles = typeof user.fileInfo === 'string' ? JSON.parse(user.fileInfo) : (user.fileInfo || []);
    } catch (e) {
        existingFiles = [];
    }

    const renderNewFilePreviews = (container) => {
        container.innerHTML = selectedFiles2.map((file, index) => `
            <div class="flex items-center gap-2 bg-white border border-brand-200 px-2 py-1.5 rounded-xl shadow-sm animate-fade-in group">
                <span class="text-[10px] font-bold text-gray-700 truncate max-w-[100px]">${escapeHtml(file.name)}</span>
                <button type="button" aria-label="Remove file" class="remove-new-file text-gray-400 hover:text-red-500 transition-colors ml-1" data-index="${index}">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/></svg>
                </button>
            </div>
        `).join('');

        container.querySelectorAll('.remove-new-file').forEach(btn => {
            btn.onclick = () => {
                const idx = parseInt(btn.getAttribute('data-index'));
                selectedFiles2.splice(idx, 1);
                renderNewFilePreviews(container);
            };
        });
    };

    const renderExistingFiles = (container) => {
        if (existingFiles.length === 0) {
            container.innerHTML = `<div class="text-[10px] text-gray-400 italic px-1">No attachments.</div>`;
            return;
        }
        container.innerHTML = existingFiles.map((url, index) => {
            const safeLink = safeUrl(url);
            if (!safeLink) return '';
            const fileName = escapeHtml(safeLink.split('?')[0].split('/').pop());
            const urlAttr = escAttr(safeLink);
            return `
                <div class="flex items-center justify-between p-2 bg-gray-50 border border-gray-100 rounded-lg hover:bg-brand-50 transition-all group mb-1">
                    <div class="flex items-center gap-2 overflow-hidden flex-1">
                        <svg class="w-3 h-3 text-brand-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        <span class="text-[10px] font-bold text-gray-600 truncate">${fileName}</span>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" data-url="${urlAttr}" aria-label="Preview ${fileName}" class="js-preview text-gray-300 hover:text-brand-500 p-0.5" title="View">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                        <a href="${urlAttr}" download target="_blank" rel="noopener noreferrer" aria-label="Download ${fileName}" class="text-gray-300 hover:text-brand-500 p-0.5" title="Download">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </a>
                        <button type="button" aria-label="Remove attachment" class="remove-existing-url text-gray-300 hover:text-red-500 p-0.5" data-index="${index}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        container.querySelectorAll('.remove-existing-url').forEach(btn => {
            btn.onclick = () => {
                const idx = parseInt(btn.getAttribute('data-index'));
                existingFiles.splice(idx, 1);
                renderExistingFiles(container);
            };
        });
    };

    Swal.fire({
        title: `<div class="text-left px-2"><span class="text-lg font-bold text-gray-900">Update Task</span></div>`,
        width: 'min(107rem, 95vw)',
        padding: '1.25rem',
        showConfirmButton: true,
        showCancelButton: true,
        confirmButtonText: 'Save Progress',
        confirmButtonColor: '#009FE3',
        customClass: { popup: 'rounded-3xl', htmlContainer: 'text-left m-0' },
        html: `
            <div class="grid grid-cols-12 gap-6 font-sans">
                <div class="col-span-12 md:col-span-5 space-y-4 md:border-r border-gray-100 md:pr-2">
                    <div class="flex items-center gap-3 p-3 bg-brand-50/50 rounded-2xl border border-brand-100">
                        <div class="w-10 h-10 bg-brand-600 rounded-xl flex items-center justify-center text-white text-lg font-black shrink-0">
                            ${escapeHtml(String(user.userEmail || 'U')[0].toUpperCase())}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-bold text-gray-900 truncate">${escapeHtml(user.userEmail || 'User')}</div>
                            <div class="text-[9px] font-bold text-brand-600 uppercase tracking-tight">${escapeHtml(user.what)}</div>
                        </div>
                        <button type="button" id="open-conversation" aria-label="Open conversation" class="p-2 hover:bg-white rounded-lg transition-colors text-brand-600 shadow-sm border border-transparent hover:border-brand-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </button>
                    </div>

                    <div>
                        <label for="swal-what-status" class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-1.5 ml-1">Status & Go Live</label>
                        <div class="flex items-center gap-2 mb-3">
                            <select id="swal-what-status" class="flex-1 border border-gray-200 rounded-xl p-2 text-xs bg-gray-50 font-bold outline-none focus:border-brand-400 transition-all">
                                <option value="pending" ${user.whatStatus === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="done" ${user.whatStatus === 'done' ? 'selected' : ''}>Go Next</option>
                                <option value="go back" ${user.whatStatus === 'go back' ? 'selected' : ''}>Go Back</option>
                            </select>

                            ${(user.idAssign != 6 && user.idAssign != 1) ? `
                            <label class="flex items-center gap-2 cursor-pointer bg-gray-50 border border-gray-200 py-2 px-3 rounded-xl hover:bg-white transition-all shrink-0">
                                <input type="checkbox" id="swal-go-live" ${user.goLive === 'yes' ? 'checked' : ''} class="w-3.5 h-3.5 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                <span class="text-[10px] font-black text-gray-700 uppercase tracking-tight">Go Live</span>
                            </label>
                            ` : ''}
                        </div>
                        
                        <div class="px-1 mb-4">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-[10px] font-bold text-gray-500">Progress</span>
                                <span id="level-display" class="text-xs font-black text-brand-600 transition-all">${parseInt(user.level, 10) || 0}%</span>
                            </div>
                            <input id="swal-level" type="range" min="0" max="100" aria-label="Progress" value="${parseInt(user.level, 10) || 0}" 
                                   class="w-full h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-brand-600 transition-all"
                                   oninput="document.getElementById('level-display').innerText = this.value + '%'">
                        </div>
                    </div>

                    <div class="pt-2">
                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-1.5 ml-1">Attachments</span>
                        <div id="existing-files-container" class="max-h-[100px] overflow-y-auto custom-scrollbar mb-3"></div>
                        <input type="file" id="swal-files-input" multiple accept="${FILE_ACCEPT}" class="sr-only">
                        <label for="swal-files-input" class="flex items-center justify-center w-full py-2 focus-within:ring-2 focus-within:ring-brand-500 border border-dashed border-gray-300 rounded-xl bg-gray-50 hover:bg-white hover:border-brand-400 transition-all cursor-pointer group">
                            <span class="text-[9px] font-black text-gray-500 uppercase group-hover:text-brand-600">+ Add Evidence</span>
                        </label>
                        <div id="new-file-previews" class="mt-2 flex flex-wrap gap-1.5"></div>
                    </div>
                </div>

                <div class="col-span-12 md:col-span-7 flex flex-col">
                    <label for="swal-desc" class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-1.5 ml-1">Work Log & Notes</label>
                    <textarea id="swal-desc" class="flex-1 w-full border border-gray-200 rounded-2xl p-4 text-sm bg-gray-50/30 outline-none focus:border-brand-400 transition-all resize-none min-h-[350px]" placeholder="Type your update here...">${escapeHtml(user.description || '')}</textarea>
                    <p class="text-[9px] text-gray-400 mt-2 px-1 italic">* Be descriptive about progress or blockers.</p>
                </div>
            </div>

            <style>
                .custom-scrollbar::-webkit-scrollbar { width: 3px; }
                .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
            </style>
        `,
        didOpen: () => {
            const statusSelect = document.getElementById('swal-what-status');
            const levelInput = document.getElementById('swal-level');
            const levelDisplay = document.getElementById('level-display');
            const goLiveCheckbox = document.getElementById('swal-go-live');
            
            // Shared function to update UI to "Done" state
            const setToDone = () => {
                statusSelect.value = 'done';
                levelInput.value = 100;
                levelDisplay.innerText = '100%';
                
                // Optional: visual feedback for automation
                levelDisplay.classList.add('scale-110', 'text-green-600');
                setTimeout(() => levelDisplay.classList.remove('scale-110'), 200);
            };

            // Status change logic
            statusSelect.onchange = (e) => {
                if (e.target.value === 'done') {
                    setToDone();
                } else if (e.target.value === 'pending' || e.target.value === 'go back' ) {
                    levelInput.value = 0;
                    levelDisplay.innerText = '0%';
                }
            };

            // Go Live logic: When checked, force Done and 100%
            if (goLiveCheckbox) {
                goLiveCheckbox.onchange = (e) => {
                    if (e.target.checked) {
                        setToDone();
                    }
                };
            }

            renderExistingFiles(document.getElementById('existing-files-container'));
            document.getElementById('open-conversation').onclick = () => openConversation(user);
            
            document.getElementById('swal-files-input').onchange = (e) => {
                selectedFiles2 = [...selectedFiles2, ...filterUploadFiles(Array.from(e.target.files), selectedFiles2.length)];
                e.target.value = '';
                renderNewFilePreviews(document.getElementById('new-file-previews'));
            };
        },
        preConfirm: async () => {
            const goLiveEl = document.getElementById('swal-go-live');
            return {
                level: parseInt(document.getElementById('swal-level').value),
                status: document.getElementById('swal-what-status').value,
                description: document.getElementById('swal-desc').value,
                goLive: goLiveEl ? (goLiveEl.checked ? 'yes' : 'no') : (user.goLive || 'no'),
                newFiles: selectedFiles2.slice(),
                remainingUrls: existingFiles
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            updateAssignment({
                assignId: user.whichId,
                idAssign: user.idAssign,
                userId: user.idUser,
                level: result.value.level,
                whatStatus: result.value.status,
                description: result.value.description,
                goLive: result.value.goLive,
                newFiles: result.value.newFiles,
                existingFiles: result.value.remainingUrls
            });
        }
    });
}


let updatingAssignment = false;
async function updateAssignment({ assignId, idAssign, userId, level, whatStatus, description, goLive, newFiles, existingFiles }) {
    if (updatingAssignment) return;
    updatingAssignment = true;

    // 1. Setup a timer to show loading ONLY after 2 seconds
    let loadingTriggered = false;
    const loadingTimer = setTimeout(() => {
        loadingTriggered = true;
        Swal.fire({
            title: 'Processing...',
            html: 'This is taking a bit longer than usual, please wait.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }, 1500);

    // Upload any new evidence files to Laravel storage, then merge with kept URLs
    let itemDataFile;
    try {
        const uploaded = await uploadFiles(newFiles);
        itemDataFile = [...(existingFiles || []), ...uploaded];
    } catch (e) {
        clearTimeout(loadingTimer);
        if (loadingTriggered) Swal.close();
        updatingAssignment = false;
        Swal.fire({ icon: 'error', title: 'Upload Failed', text: e.message || 'Could not upload files.' });
        return;
    }

    const payload = {
        type: 4,
        assignId,
        idAssign,
        userId,
        level,
        whatStatus,
        description,
        goLive,
        itemDataFile
    };

    legacyJson(4, payload)
    .then(data => {
        // 2. Clear the timer immediately upon response
        clearTimeout(loadingTimer);
        updatingAssignment = false;

        if (data.result === 'success') {
            // If the loader was already shown, this will replace it.
            // If not, it just shows the success toast.
            Swal.fire({ 
                icon: 'success', 
                title: 'Saved', 
                toast: true, 
                position: 'top-end', 
                showConfirmButton: false, 
                timer: 2000 
            });
            if (typeof fetchData === "function") fetchData(); 
        } else {
            Swal.fire({ 
                icon: 'error', 
                title: 'Update Failed', 
                text: data.message || 'Error', 
                confirmButtonColor: '#009FE3' 
            });
        }
    })
    .catch(err => {
        clearTimeout(loadingTimer); updatingAssignment = false;
        Swal.fire({ 
            icon: 'error', 
            title: 'Connection Error', 
            text: 'Server unreachable.' 
        });
    });
}

// ✅ Constants and State Management
const MyUserId = {{ $userId }};
const commentMap = {}; // Tracks conversation IDs
let selectedFiles2 = []; // Tracks files currently in the "upload queue"

/**
 * Global Helper: Removes a specific file from the selection queue
 */
window.removeFileFromQueue = (index) => {
    selectedFiles2.splice(index, 1);
    const gallery = document.getElementById('file-preview-gallery');
    if (selectedFiles2.length === 0) {
        gallery.classList.add('hidden');
        gallery.innerHTML = '';
    } else {
        renderFilePreviews(gallery);
    }
};

/**
 * Renders the small file chips in the preview area
 */
function renderFilePreviews(container) {
    container.classList.remove('hidden');
    container.innerHTML = selectedFiles2.map((file, index) => `
        <div class="flex items-center gap-2 bg-white border border-brand-200 px-2 py-1.5 rounded-xl shadow-sm animate-fade-in group">
            <div class="bg-brand-100 p-1 rounded-md text-brand-600">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            </div>
            <span class="text-[10px] font-bold text-gray-700 truncate max-w-[120px]">${escapeHtml(file.name)}</span>
            <button type="button" aria-label="Remove file" onclick="removeFileFromQueue(${index})" class="text-gray-400 hover:text-red-500 transition-colors ml-1">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/></svg>
            </button>
        </div>
    `).join('');
}

function openConversation(user) {
    const key = `${user.whichId}-${user.idAssign}-${user.idUser}`;
    if (commentMap[key]) user.comment = commentMap[key];

    Swal.fire({
        width: 'min(34rem, 95vw)',
        showConfirmButton: false,
        showCloseButton: true,
        customClass: { popup: 'rounded-3xl shadow-2xl overflow-hidden' },
        html: `
            <div class="flex flex-col h-[min(650px,70vh)] font-sans">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-2 px-1">
                    <button type="button" id="back-to-user" aria-label="Back" class="text-gray-400 hover:text-gray-900 transition-colors p-2 bg-gray-50 rounded-full">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </button>
                    <div class="text-center">
                        <div class="font-black text-gray-900 tracking-tight">Thread of Comment</div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">${escapeHtml(user.userEmail)}</div>
                    </div>
                    <div class="w-10"></div>
                </div>

                <div id="chat-container" class="flex-1 overflow-y-auto p-4 bg-gray-50/50 rounded-2xl space-y-4 custom-scrollbar border border-gray-100"></div>

                <div id="file-preview-gallery" class="hidden flex flex-wrap gap-2 p-3 bg-brand-50/40 border-t border-brand-100/50 mt-2 rounded-t-2xl"></div>

                <div id="chat-error-container" class="hidden px-4 pt-2">
                    <div class="bg-red-50 text-red-600 text-[11px] font-bold p-2 rounded-xl border border-red-100 flex items-center gap-2 animate-pulse">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span id="chat-error-text"></span>
                    </div>
                </div>

                <div class="pt-3 space-y-2">
                    <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-2xl p-2 shadow-sm focus-within:ring-2 focus-within:ring-brand-100 transition-all">
                        <label aria-label="Attach files" class="p-2 text-gray-400 hover:text-brand-600 cursor-pointer transition-colors bg-gray-50 rounded-xl focus-within:ring-2 focus-within:ring-brand-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <input id="chat-files" type="file" multiple accept="${FILE_ACCEPT}" class="sr-only">
                        </label>
                        <input id="chat-input" type="text" aria-label="Message" placeholder="Type message..." class="flex-1 outline-none text-sm text-gray-700 bg-transparent px-2">
                        <button type="button" id="chat-send" aria-label="Send message" class="bg-brand-600 text-white p-2 rounded-xl hover:bg-brand-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed" style="min-width: 36px; min-height: 36px;">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        `,
        didOpen: () => {
            const container = document.getElementById('chat-container');
            const input = document.getElementById('chat-input');
            const sendBtn = document.getElementById('chat-send');
            const fileInput = document.getElementById('chat-files');
            const gallery = document.getElementById('file-preview-gallery');
            const errorBar = document.getElementById('chat-error-container');
            const errorText = document.getElementById('chat-error-text');

            selectedFiles2 = [];
            let isSending = false;

            // Back Button Logic
            document.getElementById('back-to-user').addEventListener('click', () => {
                Swal.close();
                setTimeout(() => openUserUpdateModal(user), 100);
            });

            // File Selection Logic
            fileInput.addEventListener('change', (e) => {
                const files = Array.from(e.target.files);
                const valid = filterUploadFiles(files, selectedFiles2.length);
                selectedFiles2 = [...selectedFiles2, ...valid];
                renderFilePreviews(gallery);
                fileInput.value = '';
            });

            // Initialize Messages
            const chat = createChatLoader(container, user, MyUserId);
            if (user.comment) {
                chat.loadMessages().then(() => {
                    container.scrollTop = container.scrollHeight;
                });
            }

            // Send Message Action
            const handleSend = async () => {
                if (isSending) return;
                
                const msg = input.value.trim();
                if (!msg && selectedFiles2.length === 0) return;

                // Reset Error State
                errorBar.classList.add('hidden');
                isSending = true;
                sendBtn.disabled = true;

                const tempId = 'msg-' + Date.now();
                let filePayload;
                try {
                    filePayload = await uploadFiles(selectedFiles2);
                } catch (upErr) {
                    errorText.innerText = upErr.message || 'File upload failed.';
                    errorBar.classList.remove('hidden');
                    isSending = false;
                    sendBtn.disabled = false;
                    return;
                }

                // Optimistic UI Update (Add message before server response)
                container.insertAdjacentHTML('beforeend', `
                    <div id="${tempId}">
                        ${renderMessage({
                            comment: msg || '[Files]',
                            idUser: MyUserId,
                            userEmail: 'You',
                            created: Math.floor(Date.now() / 1000)
                        }, MyUserId)}
                    </div>
                `);
                container.scrollTop = container.scrollHeight;

                // Capture data and clear input
                const originalMsg = msg;
                const originalFiles = [...selectedFiles2];
                input.value = '';
                selectedFiles2 = [];
                gallery.classList.add('hidden');
                gallery.innerHTML = '';

                try {
                    const res = await sendData({
                        type: 9,
                        assignId: user.whichId,
                        idAssign: user.idAssign,
                        userId: user.idUser,
                        comment: originalMsg,
                        comment_file: filePayload,
                        commentId: user.comment || ''
                    });

                    if (res.result === 'error') {
                        // REMOVE optimistic message on error
                        document.getElementById(tempId).remove();
                        
                        // RESTORE input so user doesn't lose data
                        input.value = originalMsg;
                        selectedFiles2 = originalFiles;
                        renderFilePreviews(gallery);

                        // SHOW inline error
                        errorText.innerText = res.message;
                        errorBar.classList.remove('hidden');
                        return;
                    }

                    // SUCCESS logic
                    if (!user.comment && res.commentId) {
                        user.comment = res.commentId;
                        commentMap[key] = res.commentId;
                    }

                    // Replace temp message with real S3 data if needed
                    if (res.files && res.files.length) {
                        document.getElementById(tempId).innerHTML = renderMessage({
                            comment: originalMsg || '[Files]',
                            idUser: MyUserId,
                            userEmail: 'You',
                            created: Math.floor(Date.now() / 1000),
                            files: res.files
                        }, MyUserId);
                    }

                } catch (err) {
                    document.getElementById(tempId).remove();
                    input.value = originalMsg;
                    errorText.innerText = "Network error. Please try again.";
                    errorBar.classList.remove('hidden');
                } finally {
                    isSending = false;
                    sendBtn.disabled = false;
                }
            };

            sendBtn.addEventListener('click', handleSend);
            input.addEventListener('keypress', (e) => { if (e.key === 'Enter') handleSend(); });
        }
    });
}
function sendData(payload) {
    return legacyJson(payload.type, payload);
}


function formatTime(ts) {
    if (!ts) return '';
    const d = new Date(parseInt(ts) * 1000);

    return d.toLocaleString('en-SG', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}


function renderMessage(msg, myUserId) {
    const isMe = String(msg.idUser) === String(myUserId);

    let filesHtml = '';
    if (msg.files && Array.isArray(msg.files) && msg.files.length) {
        filesHtml = msg.files.map(f => {
            const url = safeUrl(f);
            if (!url) return '';
            const urlAttr = escAttr(url);
            const fNameRaw = url.split('?')[0].split('/').pop();
            const fName = escapeHtml(fNameRaw);
            const fExt = fNameRaw.split('.').pop().toLowerCase();
            const isImage = ['jpg','jpeg','png','gif','webp'].includes(fExt);
            const isVideo = ['mp4','mov','avi','mkv','webm'].includes(fExt);

            let thumbHtml = '';
            if (isImage) {
                thumbHtml = `<button type="button" data-url="${urlAttr}" aria-label="Preview ${fName}" class="js-preview block w-full"><img src="${urlAttr}" alt="${fName}" class="w-full h-28 object-cover rounded-t-lg cursor-pointer"></button>`;
            } else if (isVideo) {
                thumbHtml = `<button type="button" data-url="${urlAttr}" aria-label="Play ${fName}" class="js-preview w-full h-28 bg-gray-900 rounded-t-lg flex items-center justify-center cursor-pointer relative">
                    <svg class="w-10 h-10 text-white/80" aria-hidden="true" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </button>`;
            }

            return `<div class="mt-2 max-w-[220px]">
                <div class="bg-white/10 backdrop-blur rounded-lg border border-white/10 overflow-hidden">
                    ${thumbHtml}
                    <div class="p-2">
                        <p class="text-[10px] font-medium truncate mb-1.5 opacity-80">${fName}</p>
                        <div class="flex gap-1">
                            <button type="button" data-url="${urlAttr}" class="js-preview flex-1 flex items-center justify-center gap-1 py-1 rounded text-[10px] font-bold bg-brand-500/20 hover:bg-brand-500/30 text-brand-600 transition-all cursor-pointer">
                                <svg class="w-3 h-3" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                View
                            </button>
                            <a href="${urlAttr}" download target="_blank" rel="noopener noreferrer" class="flex-1 flex items-center justify-center gap-1 py-1 rounded text-[10px] font-bold bg-gray-500/20 hover:bg-gray-500/30 text-gray-600 transition-all">
                                <svg class="w-3 h-3" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Save
                            </a>
                        </div>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    return `
    <div class="flex ${isMe ? 'justify-end' : 'justify-start'} animate-fade-in">
        <div class="max-w-[85%]">
            <div class="text-[10px] font-bold text-gray-400 mb-1 px-1 ${isMe ? 'text-right' : 'text-left'}">
                ${isMe ? 'You' : escapeHtml(msg.userEmail || 'Partner')}
            </div>
            <div class="${isMe ? 'bg-brand-600 text-white rounded-l-2xl rounded-tr-2xl' : 'bg-white border border-gray-100 text-gray-800 rounded-r-2xl rounded-tl-2xl'} px-4 py-2.5 shadow-sm text-sm">
                <div class="leading-relaxed break-words whitespace-pre-wrap">${escapeHtml(msg.comment)}</div>
                ${filesHtml}
                <div class="text-[9px] mt-2 opacity-60 text-right font-medium">
                    ${escapeHtml(formatTime(msg.created))}
                </div>
            </div>
        </div>
    </div>
    `;
}

function createChatLoader(container, user, myUserId) {
    let page = 1;
    let loading = false;
    let hasMore = true;

    const loadMessages = async () => {
        if (!user.comment || loading || !hasMore) return;

        loading = true;

        let res;
        try {
            res = await legacyApi(10, { commentId: user.comment, page, limit: 10 });
        } catch (xhr) {
            loading = false;
            hasMore = false;
            const st = xhr && xhr.status;
            const m = st === 403 ? "You don't have access to this conversation."
                    : st === 404 ? 'This conversation could not be found.'
                    : apiMsg(xhr, 'Could not load messages.');
            container.insertAdjacentHTML('afterbegin', '<div class="text-center text-xs text-red-500 py-2">' + escapeHtml(m) + '</div>');
            return;
        }

            if (res.result === 'success') {
            const data = res.data || [];

            if (!data.length) {
                hasMore = false;
                return;
            }

            const prevHeight = container.scrollHeight;

            data.forEach(msg => {
                // ✅ Parse commentFile if it's JSON
                let files = [];
                if (msg.commentFile) {
                    try {
                        files = JSON.parse(msg.commentFile);
                    } catch (e) {
                        files = [];
                    }
                }

                container.insertAdjacentHTML(
                    'afterbegin',
                    renderMessage({
                        ...msg,
                        files // pass as array of URLs
                    }, myUserId)
                );
            });

            container.scrollTop = container.scrollHeight - prevHeight;
            page++;
        }

        loading = false;
    };

    // scroll trigger
    container.addEventListener('scroll', () => {
        if (container.scrollTop === 0) {
            loadMessages();
        }
    });

    return { loadMessages };
}













function confirmRemoveUser(event, user) {
    // Prevent the badge click (Update Modal) from firing
    if (event && event.stopPropagation) event.stopPropagation();

    const userEmail = String(user.userEmail || user.idUser || '');

    Swal.fire({
        title: null,
        html: `
            <div class="text-left font-sans">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center text-red-600 shadow-sm border border-red-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-gray-800 leading-tight">Remove Member?</h3>
                        <p class="text-[11px] font-bold text-red-400 uppercase tracking-widest">Revoke Access</p>
                    </div>
                </div>

                <p class="text-sm text-gray-500 mb-5 leading-relaxed">
                    You are about to remove <span class="font-bold text-gray-900 underline decoration-red-200 underline-offset-4">${escapeHtml(userEmail)}</span> from this project assignment.
                </p>
                

                
                <div class="mt-5 flex items-start gap-3 p-3 bg-gray-50 rounded-xl border border-gray-200/60">
                    <div class="mt-0.5 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-[11px] leading-relaxed text-gray-500 font-medium">
                        The user will lose immediate access to this specific task. This action can be undone by re-adding them later.
                    </p>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Remove User',
        cancelButtonText: 'Keep Member',
        confirmButtonColor: '#ef4444', // Tailwind Red-500
        cancelButtonColor: '#ffffff',
        reverseButtons: true,
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-[32px] p-8 shadow-2xl border border-gray-100',
            confirmButton: 'bg-red-500 hover:bg-red-600 text-white px-8 py-3.5 rounded-2xl text-sm font-bold transition-all shadow-lg shadow-red-100 ml-3',
            cancelButton: 'text-gray-400 hover:text-gray-600 px-8 py-3.5 text-sm font-bold transition-colors'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            removeUserAssignment({
                assignId: user.whichId,
                idAssign: user.idAssign,
                userId: user.idUser
            });
        }
    });
}
/**
 * Logic: API Removal
 */
let removingNow = false;
function removeUserAssignment({ assignId, idAssign, userId }) {
    const payload = { 
        assignId, 
        idAssign, 
        userId 
    };
    
    if (removingNow) return;
    removingNow = true;

    // Show loading only if request takes longer than 1.5 seconds
    let loadingTriggered = false;
    const loadingTimer = setTimeout(() => {
        loadingTriggered = true;
        Swal.fire({
            title: 'Processing...',
            html: 'This is taking a bit longer than usual, please wait.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    }, 1500);

    legacyJson(5, payload)
    .then(data => {
        removingNow = false;
        clearTimeout(loadingTimer);
        if (loadingTriggered) Swal.close();
        if(data.result === 'success') {
            Swal.fire({ 
                icon: 'success', 
                title: 'User Deleted', 
                toast: true, 
                position: 'top-end', 
                showConfirmButton: false, 
                timer: 2000 
            });
            // Refresh table to show the new user badge
            if (typeof fetchData === "function") fetchData(); 
        } else {
            Swal.fire({ icon: 'error', title: 'Failed to remove user', text: data.message || 'Could not remove the user.' });
        }
    })
    .catch(err => {
        removingNow = false;
        clearTimeout(loadingTimer);
        if (loadingTriggered) Swal.close();
        Swal.fire({ icon: 'error', title: 'Failed to remove user', text: 'Something went wrong. Please try again.' });
    });
}


    function debounce(func, wait) {
        let timeout;
        return function () {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                func.apply(context, args);
            }, wait);
        };
    }

    // Apply debounce to input field change event
    $(document).ready(function () {
        const $modal = $('#modalContainer');

        // Open
        $('#openModal').on('click', function () {
            $modal.fadeIn(300, function () { $('#titleDisplay').trigger('focus'); });
            $('body').css('overflow', 'hidden');

            // Re-render categories dropdown from already-loaded data
            populateCategoryDropdown('categoryType');

            // Initialize Quill editor for Add modal (only once)
            if (!window._addQuill) {
                window._addQuill = new Quill('#description-editor-fw', {
                    modules: { toolbar: '#description-toolbar-fw' },
                    theme: 'snow',
                    placeholder: 'Enter a detailed description with formatting...'
                });
            }
        });

        // Close
        $('.closeModal, #modalOverlay').on('click', function () {
            $modal.fadeOut(200);
            $('body').css('overflow', 'auto');
            $('#openModal').trigger('focus');
        });
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && $modal.is(':visible') && !Swal.isVisible()) {
                $('.closeModal').first().trigger('click');
            }
        });

        // Form Submit
        let selectedFiles = [];

        $('#myForm').on('submit', async function (e) {
            e.preventDefault();


              const form = this; // 👈 store form reference

            const $submitBtn = $(this).find('button[type="submit"]');
            $submitBtn.prop('disabled', true).text('Processing...');

            const fields = {
                title: { id: "titleDisplay", required: true, label: "Title", type: "input" },
                categoryType: { id: "categoryTypeValue", required: true, label: "Category", type: "input" },
                description: { id: "description-editor-fw", required: true, label: "Description", type: "quill" },
                caseInformation: { id: "caseInformation", required: true, label: "Case Information", type: "input" },
                itemDataFile: { id: "fileInput", required: false, label: "Attachments", type: "file" },
                typeOfStatus: { id: "typeOfStatus", required: true, label: "Type of Status", type: "input" },
                priority: { id: "priority", required: false, label: "Priority", type: "checkbox" },
                asignToDeVId: {
                    id: "asignToDeVIdContainer", // This assumes IDs are tags inside this container
                    required: false,
                    label: "Assign To Dev",
                    type: "array"
                },
                generecTesterId: {
                    id: "generecTesterIdContainer", // This assumes IDs are tags inside this container
                    required: false,
                    label: "Generec Tester Id",
                    type: "array"
                },
                sandboxTesterId: {
                    id: "sandboxTesterIdContainer", // This assumes IDs are tags inside this container
                    required: false,
                    label: "Sandbox Tester Id",
                    type: "array"
                },
                alphaTesterId: {
                    id: "alphaTesterIdContainer", // This assumes IDs are tags inside this container
                    required: false,
                    label: "Alpha Tester Id",
                    type: "array"
                },
                betaTesterId: {
                    id: "betaTesterIdContainer", // This assumes IDs are tags inside this container
                    required: false,
                    label: "Beta Tester Id",
                    type: "array"
                },
                liveTesterId: {
                    id: "liveTesterIdContainer", // This assumes IDs are tags inside this container
                    required: false,
                    label: "live Tester Id",
                    type: "array"
                },
                versionType: { id: "versionType", required: true, label: "Version Type", type: "input" },
                mainstatus: { id: "mainstatus", required: false, label: "Main Status", type: "input" },
                versionNumber: { id: "versionNumber", required: false, label: "Version Number", type: "input" }
            };

            let data = { type: 2 };
            let errors = [];

            for (const key of Object.keys(fields)) {
                const config = fields[key];
                let value;

                if (config.type === "file") {
                    if (selectedFiles && selectedFiles.length > 0) {
                        try {
                            value = await uploadFiles(selectedFiles);
                        } catch (err) {
                            errors.push(err.message || "Error uploading files.");
                        }
                    } else {
                        value = [];
                    }
                } else if (config.type === "checkbox") {
                    // Handle checkbox field - get boolean value
                    value = $(`#${config.id}`).is(':checked');
                } else if (config.type === "array") {
                    value = [];
                    // Look for elements with data-user-id inside your container
                    $(`#${config.id}`).find('[data-user-id]').each(function () {
                        value.push($(this).attr('data-user-id'));
                    });
                } else if (config.type === "quill") {
                    // Get HTML content from Quill editor
                    if (window._addQuill) {
                        const html = window._addQuill.root.innerHTML;
                        // Treat empty Quill content as empty string
                        value = (html === '<p><br></p>' || html.trim() === '') ? '' : html;
                    } else {
                        value = '';
                    }
                } else {
                    value = $(`#${config.id}`).val()?.trim();
                }

                data[key] = value;

                // Check requirement
                const isEmpty = (config.type === "file" || config.type === "array")
                    ? value.length === 0
                    : (config.type === "checkbox")
                    ? false  // checkboxes are never "empty"
                    : !value;

                if (config.required && isEmpty) {
                    errors.push(`${config.label} is required.`);
                }
            }

            if (errors.length > 0) {
                // Show inline error banner
                const $banner = $('#form-error-banner');
                const $list = $('#form-error-list');
                $list.empty();
                errors.forEach(err => $list.append($('<li></li>').text(err)));
                $banner.removeClass('hidden');
                $banner[0].scrollIntoView({ behavior: 'smooth', block: 'center' });

                // Highlight each errored field wrapper
                $('.field-validation-error').removeClass('field-validation-error');
                const wrapperMap = {
                    'Title': 'field-wrap-title',
                    'Description': 'field-wrap-description-fw',
                    'Case Information': 'field-wrap-caseInfo',
                    'Version Type': 'field-wrap-versionType',
                    'Type of Status': 'field-wrap-typeOfStatus',
                    'Assign To Dev': 'asignToDeVIdContainer',
                    'Generec Tester Id': 'generecTesterIdContainer',
                    'Sandbox Tester Id': 'sandboxTesterIdContainer',
                    'Alpha Tester Id': 'alphaTesterIdContainer',
                    'Beta Tester Id': 'betaTesterIdContainer',
                    'live Tester Id': 'liveTesterIdContainer'
                };
                errors.forEach(err => {
                    for (const [label, wrapperId] of Object.entries(wrapperMap)) {
                        if (err.includes(label)) {
                            const el = document.getElementById(wrapperId);
                            if (el) el.classList.add('field-validation-error');
                        }
                    }
                });

                $submitBtn.prop('disabled', false).text('Submit Request');
                return;
            }

            // Clear errors if valid
            $('#form-error-banner').addClass('hidden');
            $('.field-validation-error').removeClass('field-validation-error');

            // --- AJAX CALL START ---

            const payload = {
                type: 2,
                ...data // your original JSON object
            };

            // Show loading only if request takes longer than 1.5 seconds
            let loadingTriggered = false;
            const loadingTimer = setTimeout(() => {
                loadingTriggered = true;
                Swal.fire({
                    title: 'Processing...',
                    html: 'This is taking a bit longer than usual, please wait.',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
            }, 1500);

            legacyAjax(2, payload, {
                success: function (response) {
                    clearTimeout(loadingTimer);
                    if (loadingTriggered) Swal.close();

                    if (response.result === 'success') {
                        Swal.fire({
                            title: 'Processed!',
                            text: 'Successfully created a Test Plan',
                            icon: 'success',
                            confirmButtonColor: '#009FE3'
                        });

                        // Cleanup
                        $modal.fadeOut(200);
                        $('#myForm')[0].reset();
                        selectedFiles = [];
                        $('#previewList').addClass('hidden').empty();
                        $('body').css('overflow', 'auto');
                        $submitBtn.prop('disabled', false).text('Submit Request');

                        // Clear Quill editor content
                        if (window._addQuill) {
                            window._addQuill.setContents([]);
                        }

                        // Clear validation state
                        $('#form-error-banner').addClass('hidden');
                        $('.field-validation-error').removeClass('field-validation-error');

                        // Clear all user tag containers
                        ['asignToDeVIdContainer','generecTesterIdContainer','sandboxTesterIdContainer',
                         'alphaTesterIdContainer','betaTesterIdContainer','liveTesterIdContainer'].forEach(id => {
                            document.getElementById(id).innerHTML = '';
                        });

                        fetchData();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: response.message,
                            confirmButtonColor: '#d33'
                        });
                    }
                    $submitBtn.prop('disabled', false).text('Submit Request');
                },
                error: function (xhr, status, error) {
                    clearTimeout(loadingTimer);
                    if (loadingTriggered) Swal.close();
                    $submitBtn.prop('disabled', false).text('Submit Request');
                    if (xhr && (xhr.status === 401 || xhr.status === 419)) return;
                    Swal.fire({
                        icon: 'error',
                        title: xhr && xhr.status === 422 ? 'Please check your input' : 'Oops...',
                        text: apiMsg(xhr, 'An error occurred while processing data. Please try again later.'),
                        confirmButtonColor: '#d33'
                    });
                }
            });





            // --- AJAX CALL END ---
        });


        $(document).on('click', '.remove-file-tag', function () {
            const indexToRemove = $(this).data('file-index');

            // Remove the item from the master array
            selectedFiles.splice(indexToRemove, 1);

            // Re-render to update the indices and the UI
            renderPreviews();
        });

        $('#fileInput').on('change', function () {
    const newFilesList = Array.from(this.files);

    const uniqueFiles = newFilesList.filter(newFile => {
        const isDuplicate = selectedFiles.some(existingFile =>
            existingFile.name === newFile.name &&
            existingFile.size === newFile.size
        );
        return !isDuplicate;
    });

    const finalAccepted = filterUploadFiles(uniqueFiles, selectedFiles.length);

    selectedFiles = selectedFiles.concat(finalAccepted);

    $(this).val('');

    renderPreviews();
});
        function renderPreviews() {
            // Clear whatever is currently in the list
            const $list = $('#previewList');
            $list.empty();

            if (selectedFiles.length === 0) {
                // $('#statusText').text("Click to add files");
                $('#previewList').addClass("hidden");
                return;
            } else {
                $('#previewList').removeClass("hidden");
            }

            // Loop through our stored array and generate the HTML for each tag
            selectedFiles.forEach((file, index) => {

                // Replicating that specific 'tag with a red close button' design
                const tagHTML = `
                <div class="relative group">
                    <div class="bg-sky-500 text-white text-sm px-4 py-2.5 rounded-lg font-medium shadow-sm max-w-[150px] truncate">
                        ${escapeHtml(file.name)}
                    </div>
                    
                    <button type="button" class="remove-file-tag absolute -top-2.5 -right-2.5 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center font-bold text-xs shadow-md opacity-90 hover:opacity-100 transform group-hover:scale-105 transition-all" data-file-index="${index}" aria-label="Remove file">
                        &times;
                    </button>
                </div>
            `;
                $list.append(tagHTML);
            });
        }

        // Handle clicking the red 'X' button using delegation




        const inputField = $('#searchInput, #columnFilter'); // Replace with your input field ID
        const debouncedFunction = debounce(function () {
            currentPage = 1;
            $('#dateFilter').val('all');
            searchText = $('#searchInput').val();
            if ($('#columnFilter').length) columnFilter = $('#columnFilter').val();

            fetchData();
        }, 500);

        inputField.on('input change', debouncedFunction);

        fetchCategoriesLightweight();
        fetchData();
    });


    // Generate pagination controls
    function generatePaginationControls(totalEntries) {
        const totalPages = Math.max(1, Math.ceil(totalEntries / rowsPerPage));
        paginationContainer.empty();
        if (totalPages <= 1) return;

        const pages = [];
        const addPage = p => { if (p >= 1 && p <= totalPages && !pages.includes(p)) pages.push(p); };
        addPage(1);
        for (let p = currentPage - 1; p <= currentPage + 1; p++) addPage(p);
        addPage(totalPages);
        pages.sort((a, b) => a - b);

        const base = 'min-w-[2.25rem] h-9 px-3 inline-flex items-center justify-center text-sm rounded-lg border transition-colors focus-visible:ring-2 focus-visible:ring-brand-500';
        const chevron = (d) => '<svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' + d + '" /></svg>';
        const navBtn = (label, target, disabled, icon) =>
            '<button type="button" class="page-btn ' + base + ' border-gray-300 ' + (disabled ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100') + '" data-page="' + target + '" aria-label="' + label + '"' + (disabled ? ' disabled' : '') + '>' + icon + '</button>';

        let html = navBtn('Previous page', currentPage - 1, currentPage <= 1, chevron('M15 19l-7-7 7-7'));
        let prev = 0;
        pages.forEach(p => {
            if (prev && p - prev > 1) html += '<span class="px-1 text-gray-400" aria-hidden="true">&hellip;</span>';
            const isCur = p === currentPage;
            html += '<button type="button" class="page-btn ' + base + ' ' + (isCur ? 'bg-brand-600 border-brand-600 text-white font-semibold' : 'border-gray-300 text-gray-700 hover:bg-gray-100') + '" data-page="' + p + '" aria-label="Page ' + p + '"' + (isCur ? ' aria-current="page"' : '') + '>' + p + '</button>';
            prev = p;
        });
        html += navBtn('Next page', currentPage + 1, currentPage >= totalPages, chevron('M9 5l7 7-7 7'));
        paginationContainer.html(html);
    }

    $(document).on('click', '.pagination-buttons .page-btn:not([disabled])', function () {
        const target = parseInt($(this).attr('data-page'), 10);
        if (!target || target === currentPage) return;
        currentPage = target;
        const sc = document.getElementById('table-scroll-container');
        if (sc) sc.scrollTop = 0;
        fetchData();
    });

function handleBinClick(event, element) {
    event.preventDefault();

    // 1. Decode the item data from the data-payload attribute
    let item;
    try {
        const payload = element.getAttribute('data-payload');
        item = JSON.parse(decodeURIComponent(escape(atob(payload))));
    } catch (e) {
        Toast.fire({ icon: "error", title: "Could not open delete dialog" });
        return;
    }
    
    const planTitle = item.title || 'this test plan';
    const confirmationWord = "CONFIRM";

    Swal.fire({
        title: null,
        html: `
            <div class="text-left font-sans max-w-full overflow-hidden">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-red-100 rounded-2xl flex items-center justify-center text-red-600 border border-red-200 flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-gray-800 leading-tight">Critical Action</h3>
                        <p class="text-[11px] font-bold text-red-500 uppercase tracking-widest font-mono">Verification Required</p>
                    </div>
                </div>

                <p class="text-sm text-gray-500 mb-4 leading-relaxed">
                    This will permanently delete <span class="font-bold text-gray-900 break-words">${escapeHtml(planTitle)}</span>.
                </p>
                
                <div class="p-3 bg-red-50 border border-red-100 rounded-xl mb-4">
                    <p class="text-[10px] text-red-700 font-bold uppercase mb-1">To proceed, please type <span class="underline">${confirmationWord}</span> below:</p>
                </div>
            </div>
        `,
        input: 'text',
        inputPlaceholder: 'Type here...',
        showCancelButton: true,
        confirmButtonText: 'Permanently Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        reverseButtons: true,
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-[32px] p-6 md:p-8 shadow-2xl border border-gray-50 max-w-[95vw] md:max-w-md',
            // Added !w-full and box-border to prevent overflow
            input: '!w-full !m-0 !mt-2 px-4 py-3 rounded-xl border-2 border-gray-100 focus:border-red-400 focus:ring-0 transition-all text-center font-bold tracking-widest uppercase box-border',
            confirmButton: 'bg-red-600 hover:bg-red-700 text-white px-8 py-3.5 rounded-2xl text-sm font-bold transition-all shadow-lg shadow-red-200 ml-3',
            cancelButton: 'text-gray-400 hover:text-gray-600 px-8 py-3.5 text-sm font-bold transition-colors'
        },
        preConfirm: (inputValue) => {
           if (inputValue.toLowerCase() !== confirmationWord.toLowerCase()) {
                Swal.showValidationMessage(`Input must match "${confirmationWord}"`);
                return false;
            }
            return true;
        },
        didOpen: () => {
            const input = Swal.getInput();
            if (input) input.focus();
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading only if request takes longer than 1.5 seconds
            let loadingTriggered = false;
            const loadingTimer = setTimeout(() => {
                loadingTriggered = true;
                Swal.fire({
                    title: 'Processing...',
                    html: 'This is taking a bit longer than usual, please wait.',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
            }, 1500);

            legacyAjax(7, { testplanId: item.id }, {
        success: function (response) {
            clearTimeout(loadingTimer);
            if (loadingTriggered) Swal.close();
    if (response.result === 'success') {
        Swal.fire({ icon: 'success', title: 'Deleted', timer: 1500, showConfirmButton: false, customClass: { popup: 'rounded-2xl' } });
        fetchData();
    } else {
        Swal.fire({ icon: 'error', title: 'Error', text: response.message || 'Delete failed.', customClass: { popup: 'rounded-2xl' } });
    }
},
        error: function(xhr) {
            clearTimeout(loadingTimer);
            if (loadingTriggered) Swal.close();
            if (xhr && (xhr.status === 401 || xhr.status === 419)) return;
            Swal.fire({ icon: 'error', title: 'Error', text: apiMsg(xhr, 'Could not connect to server.'), customClass: { popup: 'rounded-2xl' } });
        }
            });
        }
    });
}



</script>
@endsection