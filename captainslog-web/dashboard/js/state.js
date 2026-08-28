// dashboard/js/state.js
export let currentProjectId = localStorage.getItem('lastProjectId') || null;
export let currentRequirements = [];
export let editingReqId = null;

export function setCurrentProjectId(id) {
    currentProjectId = id;
    if (id) {
        localStorage.setItem('lastProjectId', id);
    } else {
        localStorage.removeItem('lastProjectId');
    }
}

export function setCurrentRequirements(reqs) { currentRequirements = reqs; }
export function setEditingReqId(id) { editingReqId = id; }
