export const ATTRIBUTE_SCHEMAS = {
    // Stakeholder & Kontext
    'STK': [
        ['email', 'E-Mail Adresse', 'input'],
        ['phone', 'Telefonnummer', 'input'],
        ['organization', 'Firma / Abteilung (z.B. epsa)', 'input'],
        ['role', 'Rolle im Projekt', 'input'],
        ['influence', 'Einfluss / Interesse', 'class']
    ],
    
    // Agile & Use Cases
    'US': [
        ['us_role', 'Als (Rolle/Person)', 'input'],
        ['us_action', 'möchte ich (Ziel/Aktion)', 'textarea'],
        ['us_benefit', 'so dass (Nutzen/Mehrwert)', 'textarea'],
        ['acceptance_criteria', 'Akzeptanzkriterien', 'textarea'],
        ['story_points', 'Story Points (Aufwand)', 'input']
    ],
    'UC': [
        ['primary_actor', 'Primärer Akteur', 'input'],
        ['preconditions', 'Vorbedingungen', 'textarea'],
        ['main_scenario', 'Hauptszenario (Erfolgsfall)', 'textarea'],
        ['alt_scenario', 'Alternativ- & Fehlerszenarien', 'textarea']
    ],

    // --- Deine bisherigen Engineering & Test Typen ---
    'USR': [
        ['priority', 'Priorität', 'class'],
        ['source', 'Stakeholder / Quelle', 'input']
    ],
    'SYS': [
        ['classification', 'Klassifikation (z.B. Sicherheitskritisch)', 'class'],
        ['verification_criteria', 'Verifikationskriterium', 'textarea']
    ],
    'SRS': [
        ['safety_relevant', 'Sicherheitsrelevant', 'class'],
        ['allocation', 'Hardware/Software Allokation', 'input']
    ],
    'SWC': [
        ['component_type', 'Komponententyp (Modul/Unit)', 'input'],
        ['interfaces', 'Schnittstellen / Ports', 'textarea']
    ],
    'TC': [
        ['test_method', 'Testmethode (Test, Inspection, Analysis, Demo)', 'input'],
        ['preconditions', 'Test-Voraussetzungen', 'textarea'],
        ['expected_result', 'Erwartetes Ergebnis', 'textarea'],
        ['test_environment', 'Testumgebung / Prüfstand', 'input']
    ],
    'TR': [
        ['execution_date', 'Durchführungsdatum', 'input'],
        ['tester', 'Tester / Verantwortlicher', 'input'],
        ['verdict', 'Testergebnis (Pass / Fail / Inconclusive)', 'class'],
        ['evidence_ref', 'Protokoll / Screenshot Referenz', 'input']
    ]
};