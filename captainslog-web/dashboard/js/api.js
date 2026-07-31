// dashboard/js/api.js
import { currentProjectId } from './state.js';

export async function fetchProjects() {
    try {
        const res = await fetch('../api/web_get_projects.php');
        const data = await res.json();
        return Array.isArray(data) ? data : (data.projects || []);
    } catch (err) {
        console.error("Fehler beim Laden der Projekte:", err);
        return [];
    }
}

export async function fetchRequirements() {
    if (!currentProjectId) return [];
    try {
        const res = await fetch(`../api/get_requirements.php?project_id=${currentProjectId}`);
        const data = await res.json();
        return Array.isArray(data) ? data : (data.requirements || []);
    } catch (err) {
        console.error("Fehler beim Laden der Anforderungen:", err);
        return [];
    }
}

export async function fetchHistory() {
    if (!currentProjectId) return [];
    try {
        const res = await fetch(`../api/get_history.php?project_id=${currentProjectId}`);
        const data = await res.json();
        return Array.isArray(data) ? data : (data.history || []);
    } catch (err) {
        console.error("Fehler beim Laden der Historie:", err);
        return [];
    }
}

export async function sendReqApi(apiUrl, payload) {
    try {
        const res = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        return await res.json();
    } catch (err) {
        console.error("API-Fehler bei POST-Request:", err);
        return { success: false, error: err.message };
    }
}