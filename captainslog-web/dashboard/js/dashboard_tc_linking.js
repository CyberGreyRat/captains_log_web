export async function loadCriteriaForTestCase(testCaseId) {
  const res = await fetch('../api/get_requirements.php?project_id=' + currentProjectId);
  const data = await res.json();
  const reqs = data.requirements || [];
  let html = '';
  reqs.forEach(r => {
    (r.acceptance_criteria || '').split('\n').forEach((c, i) => {
      if (c.trim()) {
        html += `<label><input type="checkbox" data-req="${r.id}" data-idx="${i}">${r.req_key} - ${c}</label><br>`;
      }
    });
  });
  document.getElementById('tcCriteriaList').innerHTML = html;
}
