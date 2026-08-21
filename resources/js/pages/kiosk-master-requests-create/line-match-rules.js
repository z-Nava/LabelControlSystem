const ASSEMBLY_PACKAGING_TYPE = 'assembly_packaging';

const ROLE_LABELS = {
    assembly: 'Job Ensamble',
    packaging: 'Job Empaque',
};

export function normalizeLineCode(value) {
    return String(value || '').trim().toUpperCase();
}

function normalizeJobNumber(value) {
    return String(value || '').trim().toUpperCase();
}

function formatOracleJob(job) {
    return `${ROLE_LABELS[job.role]} ${job.jobNumber} (${job.line})`;
}

function joinOracleJobs(jobs) {
    if (jobs.length === 1) {
        return formatOracleJob(jobs[0]);
    }

    return `${formatOracleJob(jobs[0])} ni ${formatOracleJob(jobs[1])}`;
}

export function evaluateMasterRequestLine({
    requestType,
    selectedLineCode,
    jobNumbers,
    jobLookups,
}) {
    const normalizedSelectedLine = normalizeLineCode(selectedLineCode);
    const normalizedRequestType = String(requestType || '').trim();
    const assemblyJobNumber = normalizeJobNumber(jobNumbers?.assembly);
    const packagingJobNumber = normalizeJobNumber(jobNumbers?.packaging);

    if (!normalizedRequestType || !normalizedSelectedLine) {
        return { status: 'idle', message: '' };
    }

    if (
        (normalizedRequestType === ASSEMBLY_PACKAGING_TYPE && !packagingJobNumber)
        || (normalizedRequestType !== ASSEMBLY_PACKAGING_TYPE && !assemblyJobNumber)
    ) {
        return { status: 'idle', message: '' };
    }

    const lineRoles = normalizedRequestType === ASSEMBLY_PACKAGING_TYPE
        ? ['assembly', 'packaging'].filter((role) => normalizeJobNumber(jobNumbers?.[role]))
        : ['assembly'];
    const lookupRoles = ['assembly', 'packaging']
        .filter((role) => normalizeJobNumber(jobNumbers?.[role]));
    const hasPendingLookup = lookupRoles.some((role) => {
        const lookup = jobLookups?.[role];

        return !lookup
            || normalizeJobNumber(lookup.job_number) !== normalizeJobNumber(jobNumbers?.[role]);
    });

    if (hasPendingLookup) {
        return {
            status: 'pending',
            message: 'Espera a que el sistema termine de validar los Jobs en Oracle.',
        };
    }

    const applicableJobs = lineRoles
        .map((role) => ({
            role,
            jobNumber: normalizeJobNumber(jobNumbers?.[role]),
            line: normalizeLineCode(jobLookups?.[role]?.line),
        }))
        .filter((job) => job.line !== '');

    if (applicableJobs.length === 0) {
        return {
            status: 'unverifiable',
            message: 'Los Jobs aplicables no tienen una línea de producción registrada en Oracle.',
        };
    }

    const matchingJobs = applicableJobs.filter((job) => job.line === normalizedSelectedLine);

    if (matchingJobs.length > 0) {
        return {
            status: 'match',
            message: `La línea ${normalizedSelectedLine} coincide con ${matchingJobs.map(formatOracleJob).join(' y ')}.`,
        };
    }

    return {
        status: 'mismatch',
        message: `La línea seleccionada ${normalizedSelectedLine} no coincide con ninguna de las líneas aplicables en Oracle: ${joinOracleJobs(applicableJobs)}.`,
    };
}

