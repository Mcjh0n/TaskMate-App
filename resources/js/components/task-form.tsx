import { PAYMENT_BOUNDARY_NOTICE } from '@/constants/notices';
import { useState } from 'react';
import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type Option = { value: string; label: string };
type TaskData = {
    title: string; category: string; description: string; requiredActivities: string | null; deliverables: string | null;
    estimatedDurationHours: string | null; skillRequirements: string | null; complexity: string | null; budget: string;
    workModeValue: string; workplaceName: string | null; workplaceAddress: string | null; deadline: string | null;
    physicalDemand: string | null; academicInvolvement: string | null; hazardExposure: boolean | null; hazardDescription: string | null;
    privateMeetingRequired: boolean | null; workplaceSetting: string | null;
};
type Props = {
    action: { action: string; method: 'post' | 'put' };
    options: { categories: Option[]; workModes: Option[]; complexities: Option[]; guidelines: string[]; riskQuestions: { physical_demand: Option[]; academic_involvement: Option[]; workplace_setting: Option[] } };
    task: TaskData | null;
    resetWarning: boolean;
};

export default function TaskForm({ action, options, task, resetWarning }: Props) {
    const [workMode, setWorkMode] = useState(task?.workModeValue ?? '');
    const [hazardExposure, setHazardExposure] = useState(task?.hazardExposure == null ? '' : task.hazardExposure ? '1' : '0');
    const hasWorkplace = workMode === 'onsite' || workMode === 'hybrid';
    const hasSavedExtraDetails = Boolean(task && (
        task.requiredActivities || task.deliverables || task.estimatedDurationHours || task.skillRequirements || task.complexity
        || task.physicalDemand || task.academicInvolvement || task.hazardExposure !== null || task.privateMeetingRequired !== null || task.workplaceSetting
    ));
    const [optionalDetailsOpen, setOptionalDetailsOpen] = useState(hasSavedExtraDetails);

    return (
        <Form {...action} className="space-y-5">
            {({ errors, processing }) => (
                <>
                    {resetWarning && <div role="alert" className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">Saving these changes will send this task back for review and clear the previous review note.</div>}

                    <section className="space-y-4 rounded-xl border p-5">
                        <div><h2 className="text-lg font-semibold">Task basics</h2><p className="mt-1 text-sm text-muted-foreground">Tell students what the work is and what result you need.</p></div>
                        <div className="grid gap-2"><Label htmlFor="title">Task title</Label><Input id="title" name="title" maxLength={150} defaultValue={task?.title} required /><InputError message={errors.title} /></div>
                        <div className="grid gap-2"><Label htmlFor="category">Category</Label><select id="category" name="category" defaultValue={task?.category ? options.categories.find((option) => option.label === task.category)?.value ?? '' : ''} className="h-10 rounded-md border bg-background px-3 text-sm" required><option value="" disabled>Select a category</option>{options.categories.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select><InputError message={errors.category} /></div>
                        <div className="grid gap-2"><Label htmlFor="description">What needs to be done?</Label><Textarea id="description" name="description" maxLength={10000} defaultValue={task?.description} placeholder="Describe the work, expected result, and anything students should know before applying." required /><p className="text-xs text-muted-foreground">Include the main activities and expected result here. More detail fields are optional.</p><InputError message={errors.description} /></div>
                    </section>

                    <section className="space-y-4 rounded-xl border p-5">
                        <h2 className="text-lg font-semibold">Budget and work setup</h2>
                        <div className="grid gap-2"><Label htmlFor="budget">Estimated budget (PHP)</Label><Input id="budget" name="budget" type="number" min="1" max="1000000" step="0.01" defaultValue={task?.budget} required /><InputError message={errors.budget} /><p className="text-sm text-muted-foreground">{PAYMENT_BOUNDARY_NOTICE}</p></div>
                        <div className="grid gap-2"><Label htmlFor="work_mode">Work mode</Label><select id="work_mode" name="work_mode" value={workMode} onChange={(event) => setWorkMode(event.target.value)} className="h-10 rounded-md border bg-background px-3 text-sm" required><option value="" disabled>Select a work mode</option>{options.workModes.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select><InputError message={errors.work_mode} /></div>
                        {hasWorkplace && <div className="space-y-3 rounded-lg border bg-muted/30 p-4"><p className="text-sm text-muted-foreground">Students see the place of work before applying. Enter a worksite, not a home address.</p><div className="grid gap-2"><Label htmlFor="workplace_name">Workplace name</Label><Input id="workplace_name" name="workplace_name" defaultValue={task?.workplaceName ?? ''} required /><InputError message={errors.workplace_name} /></div><div className="grid gap-2"><Label htmlFor="workplace_address">Workplace address</Label><Input id="workplace_address" name="workplace_address" defaultValue={task?.workplaceAddress ?? ''} required /><InputError message={errors.workplace_address} /></div></div>}
                        <div className="grid gap-2"><Label htmlFor="deadline">Deadline (optional)</Label><Input id="deadline" name="deadline" type="date" min={new Date(Date.now() + 86400000).toISOString().slice(0, 10)} defaultValue={task?.deadline ?? ''} /><InputError message={errors.deadline} /></div>
                    </section>

                    <details open={optionalDetailsOpen || Boolean(errors.required_activities || errors.deliverables || errors.skill_requirements || errors.estimated_duration_hours || errors.complexity || errors.physical_demand || errors.academic_involvement || errors.hazard_exposure || errors.hazard_description || errors.private_meeting_required || errors.workplace_setting)} onToggle={(event) => setOptionalDetailsOpen(event.currentTarget.open)} className="rounded-xl border p-5">
                        <summary className="cursor-pointer text-lg font-semibold">Optional details and safety information</summary>
                        <div className="mt-4 space-y-5">
                            <p className="text-sm text-muted-foreground">Add details that help students decide whether this task suits them. If you leave a safety answer blank, students will see that it was not provided and the task will still go through review.</p>
                            <div className="grid gap-4 rounded-lg border p-4">
                                <h3 className="font-medium">More about the work</h3>
                                <div className="grid gap-2"><Label htmlFor="required_activities">Main activities (optional)</Label><Textarea id="required_activities" name="required_activities" maxLength={10000} defaultValue={task?.requiredActivities ?? ''} /><InputError message={errors.required_activities} /></div>
                                <div className="grid gap-2"><Label htmlFor="deliverables">Expected result or deliverables (optional)</Label><Textarea id="deliverables" name="deliverables" maxLength={10000} defaultValue={task?.deliverables ?? ''} /><InputError message={errors.deliverables} /></div>
                                <div className="grid gap-2"><Label htmlFor="skill_requirements">Skills or experience needed (optional)</Label><Textarea id="skill_requirements" name="skill_requirements" maxLength={5000} defaultValue={task?.skillRequirements ?? ''} /><InputError message={errors.skill_requirements} /></div>
                                <div className="grid gap-2"><Label htmlFor="estimated_duration_hours">Estimated time in hours (optional)</Label><Input id="estimated_duration_hours" name="estimated_duration_hours" type="number" min="0.5" max="500" step="0.1" defaultValue={task?.estimatedDurationHours ?? ''} /><InputError message={errors.estimated_duration_hours} /></div>
                                <div className="grid gap-2"><Label htmlFor="complexity">Complexity (optional)</Label><select id="complexity" name="complexity" defaultValue={task?.complexity ? options.complexities.find((option) => option.label === task.complexity)?.value ?? '' : ''} className="h-10 rounded-md border bg-background px-3 text-sm"><option value="">Not provided</option>{options.complexities.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select><InputError message={errors.complexity} /></div>
                            </div>

                            <div className="grid gap-4 rounded-lg border p-4">
                                <div><h3 className="font-medium">Safety information (optional)</h3><p className="mt-1 text-sm text-muted-foreground">Your answers help with review. Screening cannot guarantee that a task is safe.</p></div>
                                <div className="grid gap-2"><Label htmlFor="physical_demand">Physical demand</Label><select id="physical_demand" name="physical_demand" defaultValue={task?.physicalDemand ?? ''} className="h-10 rounded-md border bg-background px-3 text-sm"><option value="">Not provided</option>{options.riskQuestions.physical_demand.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select><InputError message={errors.physical_demand} /></div>
                                <div className="grid gap-2"><Label htmlFor="academic_involvement">Academic involvement</Label><select id="academic_involvement" name="academic_involvement" defaultValue={task?.academicInvolvement ?? ''} className="h-10 rounded-md border bg-background px-3 text-sm"><option value="">Not provided</option>{options.riskQuestions.academic_involvement.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select><InputError message={errors.academic_involvement} /></div>
                                <div className="grid gap-2"><Label htmlFor="hazard_exposure">Does the task involve hazards?</Label><select id="hazard_exposure" name="hazard_exposure" value={hazardExposure} onChange={(event) => setHazardExposure(event.target.value)} className="h-10 rounded-md border bg-background px-3 text-sm"><option value="">Not provided</option><option value="0">No</option><option value="1">Yes</option></select><InputError message={errors.hazard_exposure} /></div>
                                {hazardExposure === '1' && <div className="grid gap-2"><Label htmlFor="hazard_description">Describe the hazards</Label><Textarea id="hazard_description" name="hazard_description" maxLength={3000} defaultValue={task?.hazardDescription ?? ''} required /><InputError message={errors.hazard_description} /></div>}
                                <div className="grid gap-2"><Label htmlFor="private_meeting_required">Does the task require a one-on-one meeting in a private or isolated place?</Label><select id="private_meeting_required" name="private_meeting_required" defaultValue={task?.privateMeetingRequired == null ? '' : task.privateMeetingRequired ? '1' : '0'} className="h-10 rounded-md border bg-background px-3 text-sm"><option value="">Not provided</option><option value="0">No</option><option value="1">Yes</option></select><InputError message={errors.private_meeting_required} /></div>
                                {hasWorkplace && <div className="grid gap-2"><Label htmlFor="workplace_setting">Workplace setting</Label><select id="workplace_setting" name="workplace_setting" defaultValue={task?.workplaceSetting ?? ''} className="h-10 rounded-md border bg-background px-3 text-sm"><option value="">Not provided</option>{options.riskQuestions.workplace_setting.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select><InputError message={errors.workplace_setting} /></div>}
                            </div>
                        </div>
                    </details>

                    <section className="space-y-4 rounded-xl border p-5">
                        <h2 className="text-lg font-semibold">Platform rules</h2>
                        <div className="rounded-lg border bg-muted/40 p-4"><h3 className="font-medium">Task guidelines</h3><ul className="mt-2 list-disc space-y-1 pl-5 text-sm">{options.guidelines.map((guideline) => <li key={guideline}>{guideline}</li>)}</ul></div>
                        <div className="flex items-start gap-2"><input id="guidelines_confirmed" name="guidelines_confirmed" type="checkbox" value="1" required className="mt-1" /><Label htmlFor="guidelines_confirmed">I confirm that this task follows the platform rules.</Label></div><InputError message={errors.guidelines_confirmed} />
                    </section>

                    <Button type="submit" disabled={processing}>{task ? 'Save changes' : 'Submit task for review'}</Button>
                </>
            )}
        </Form>
    );
}
