import { currentProjectId } from './state.js';

export async function fetchProjects() {
    try {
        const res = await fetch('../api/web_get_projects.php');
        const data = await res.json();
        return data.projects || [];
    } catch (err) {
        console.error("Fehler beim Laden der Projekte", err);
        return [];
    }
}

export async function fetchRequirements() {
    if (!currentProjectId) return [];
    try {
        const res = await fetch(`../api/web_get_reqs.php?project_id=${currentProjectId}`);
        const data = await res.json();
        return data.requirements || [];
    } catch (err) {
        console.error("Fehler beim Laden der Anforderungen", err);
        return [];
    }
}

export async function fetchHistory() {
    if (!currentProjectId) return [];
    try {
        const res = await fetch(`../api/web_get_history.php?project_id=${currentProjectId}`);
        const data = await res.json();
        return data.history || [];
    } catch (err) {
        console.error("Fehler beim Laden der Historie", err);
        return [];
    }
}

export async function sendReqApi(apiUrl, payload) {
    const res = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    return await res.json();
}

export async function fetchReqHistory(requirementId) {
    if (!requirementId) return [];
    try {
        const res = await fetch(`../api/web_get_req_history.php?requirement_id=${requirementId}`);
        const data = await res.json();
        return data.history || [];
    } catch (err) {
        console.error("Fehler beim Laden der Anforderungs-Historie", err);
        return [];
    }
}