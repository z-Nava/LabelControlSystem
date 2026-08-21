export function getMasterRequestElements() {
    const form = document.getElementById('masterRequestCreate');

    if (!form) {
        return null;
    }

    return {
        form,
        requestSource: form.dataset.requestSource || 'kiosk',
        validationFlow: form.dataset.validationFlow || '',
        lookupUrl: form.dataset.lookupUrl,
        fields: {
            requestDate: document.getElementById('requestDate'),
            lineTypeSelect: document.getElementById('lineTypeSelect'),
            lineSelect: document.getElementById('lineSelect'),
            localInput: document.getElementById('localInput'),
            subinventoryInput: document.getElementById('subinventoryInput'),
            shiftSelect: document.getElementById('shiftSelect'),
            requestType: document.getElementById('requestType'),
            jobAssembly: document.getElementById('jobAssembly'),
            jobPackaging: document.getElementById('jobPackaging'),
            poNumber: document.getElementById('poNumber'),
            destination: document.getElementById('destination'),
            modelDisplay: document.getElementById('modelDisplay'),
            modelMappingWarning: document.getElementById('modelMappingWarning'),
            qtyAssembly: document.getElementById('jobAssemblyQty'),
            qtyPackaging: document.getElementById('jobPackagingQty'),
            hintAssembly: document.getElementById('jobAssemblyHint'),
            hintPackaging: document.getElementById('jobPackagingHint'),
        },
        preview: {
            date: document.getElementById('previewDate'),
            lineShift: document.getElementById('previewLineShift'),
            jobs: document.getElementById('previewJobs'),
            type: document.getElementById('previewType'),
        },
        lineMatchStatus: {
            container: document.getElementById('lineMatchStatus'),
            title: document.getElementById('lineMatchStatusTitle'),
            message: document.getElementById('lineMatchStatusMessage'),
        },
        jobDrivenContext: {
            jobs: {
                assembly: {
                    container: document.getElementById('jobAssemblyContext'),
                    line: document.getElementById('jobAssemblyLine'),
                    lineType: document.getElementById('jobAssemblyLineType'),
                    inventory: document.getElementById('jobAssemblyInventory'),
                },
                packaging: {
                    container: document.getElementById('jobPackagingContext'),
                    line: document.getElementById('jobPackagingLine'),
                    lineType: document.getElementById('jobPackagingLineType'),
                    inventory: document.getElementById('jobPackagingInventory'),
                },
            },
            status: {
                container: document.getElementById('productionContextStatus'),
                title: document.getElementById('productionContextStatusTitle'),
                message: document.getElementById('productionContextStatusMessage'),
            },
            lineDifference: {
                container: document.getElementById('lineDifferenceStatus'),
                message: document.getElementById('lineDifferenceMessage'),
            },
            officialLine: document.getElementById('officialLineDisplay'),
        },
        folioValidation: {
            container: document.getElementById('folioLiveValidation'),
        },
        summary: {
            jobs: document.getElementById('summaryJobs'),
            type: document.getElementById('summaryType'),
            officialLine: document.getElementById('summaryOfficialLine'),
            inventory: document.getElementById('summaryInventory'),
            folios: document.getElementById('summaryFolios'),
            request: document.getElementById('summaryRequest'),
        },
    };
}

export function getFieldValue(form, fieldName) {
    return (form.elements.namedItem(fieldName)?.value || '').trim();
}

export function clearCustomValidity(form, fieldName) {
    form.elements.namedItem(fieldName)?.setCustomValidity('');
}

