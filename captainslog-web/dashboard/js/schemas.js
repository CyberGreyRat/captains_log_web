export const ATTRIBUTE_SCHEMAS = {
    CLASS: [['software_system', 'Softwaresystem', 'text'], ['software_safety_class', 'Software-Sicherheitsklasse', 'class'], ['classification_responsible', 'Klassifizierungsverantwortlich', 'text'], ['classification_rationale', 'Klassifizierungsbegründung', 'textarea']],
    RISK: [['hazard', 'Gefährdung', 'textarea'], ['hazardous_situation', 'Gefährdungssituation', 'textarea'], ['harm', 'Schaden', 'textarea'], ['initial_severity', 'Anfänglicher Schweregrad', 'text'], ['initial_probability', 'Anfängliche Wahrscheinlichkeit', 'text']],
    SRS: [['classification_ref', 'Klassifizierungsreferenz', 'CLASS'], ['software_item_safety_class', 'Software-Sicherheitsklasse', 'class']],
    SEC: [['classification_ref', 'Klassifizierungsreferenz', 'CLASS'], ['software_item_safety_class', 'Software-Sicherheitsklasse', 'class']],
    UT: [['test_method', 'Testmethode', 'textarea'], ['expected_result', 'Erwartetes Ergebnis', 'textarea']],
    IT: [['test_method', 'Testmethode', 'textarea'], ['expected_result', 'Erwartetes Ergebnis', 'textarea']]
};