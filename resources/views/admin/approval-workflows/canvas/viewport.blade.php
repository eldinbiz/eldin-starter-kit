{{-- Center Canvas Viewport: Dot Grid, SVG Connectors, Pipeline Stages, and Pan/Zoom --}}
<main
    id="canvas-viewport"
    @mousedown="startPan($event)"
    @mousemove="doPan($event)"
    @mouseup="endPan()"
    @mouseleave="endPan()"
    @wheel.prevent="onWheel($event)"
    @click="deselectAll()"
    class="relative flex-1 overflow-hidden bg-gray-50/50 dark:bg-gray-950 select-none cursor-default"
    style="background-image: radial-gradient(rgba(156, 163, 175, 0.3) 1px, transparent 1px); background-size: 24px 24px;"
>
    {{-- Floating Controls Toolbar --}}
    @include('admin.approval-workflows.canvas.controls')

    {{-- Transform Layer for Pan & Zoom --}}
    <div
        id="canvas-transform-layer"
        class="absolute inset-0 p-16 origin-top-left transition-transform duration-75 ease-out"
        :style="`transform: translate(${panX}px, ${panY}px) scale(${zoom}); transform-origin: 0 0;`"
    >
        {{-- Pipeline Flow Column (Centered) --}}
        <div id="pipeline-flow-column" class="relative z-10 flex flex-col items-center w-full max-w-4xl mx-auto py-8">
            {{-- 1. START NODE: Requester --}}
            <div id="node-start" class="relative flex flex-col items-center">
                <div class="flex items-center gap-2.5 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-bold text-blue-700 shadow-sm dark:border-blue-800/80 dark:bg-blue-950/60 dark:text-blue-300">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-white text-[10px]">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </span>
                    <span>START: Requester Submission</span>
                </div>
            </div>

            {{-- Connector: Start Node -> First Stage --}}
            <div class="pipeline-connector">
                <div class="pipeline-connector-line"></div>
                <div class="pipeline-connector-arrow"></div>
            </div>

            {{-- 2. SORTABLE STAGES PIPELINE CONTAINER --}}
            <div id="pipeline-stages" class="flex flex-col items-center w-full">
                <template x-for="(group, gIdx) in groups" :key="gIdx">
                    <div class="pipeline-stage-wrapper flex flex-col items-center w-full">
                        @include('admin.approval-workflows.canvas.stage-container')

                        {{-- Connector Between Stages --}}
                        <template x-if="gIdx < groups.length - 1">
                            <div class="pipeline-connector">
                                <div class="pipeline-connector-line"></div>
                                <button
                                    type="button"
                                    @click.stop="insertGroup(gIdx)"
                                    class="pipeline-connector-btn group"
                                    title="Insert stage here"
                                >
                                    <svg class="h-3 w-3 text-gray-400 group-hover:text-brand-600 transition" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    <span class="hidden group-hover:inline ml-1 text-[10px] text-brand-600 font-semibold">Add Stage</span>
                                </button>
                                <div class="pipeline-connector-arrow"></div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Connector: Last Stage -> Finish Node --}}
            <div class="pipeline-connector">
                <div class="pipeline-connector-line"></div>
                <button
                    type="button"
                    id="add-stage-dropzone"
                    @click.stop="addGroup()"
                    class="pipeline-connector-btn group"
                    title="Add new stage"
                >
                    <svg class="h-3 w-3 text-gray-400 group-hover:text-brand-600 transition" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span class="inline ml-1 text-[10px] text-gray-600 group-hover:text-brand-600 dark:text-gray-300 font-semibold">Add Step Group</span>
                </button>
                <div class="pipeline-connector-arrow"></div>
            </div>

            {{-- 3. COMPLETION NODE: Approved --}}
            <div id="node-finish" class="relative flex flex-col items-center">
                <div class="flex items-center gap-2.5 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-700 shadow-sm dark:border-emerald-800/80 dark:bg-emerald-950/60 dark:text-emerald-300">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600 text-white text-[10px]">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </span>
                    <span>FINISH: Approved &amp; Executed</span>
                </div>
            </div>
        </div>
    </div>
</main>
