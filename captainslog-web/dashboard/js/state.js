export let currentProjectId = null;
export let currentRequirements = [];
export let editingReqId = null;

export function setCurrentProjectId(id) { currentProjectId = id; }
export function setCurrentRequirements(reqs) { currentRequirements = reqs; }
export function setEditingReqId(id) { editingReqId = id; }