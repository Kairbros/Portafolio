// id del tipo de un proyecto, venga como campo suelto o anidado
export function projectTypeId(project: any): string {
    return String(project?.type_project_id ?? project?.type?.id ?? '')
}
