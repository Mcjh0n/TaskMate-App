import { useState } from 'react';
import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type Option = { value: string; label: string };
type TaskData = {
    title: string; category: string; description: string; requiredActivities: string; deliverables: string;
    estimatedDurationHours: string; skillRequirements: string | null; complexity: string; budget: string;
    workModeValue: string; workplaceName: string | null; workplaceAddress: string | null; deadline: string | null;
};
type Props = {
    action: { url: string; method: string };
    options: { categories: Option[]; workModes: Option[]; complexities: Option[]; guidelines: string[] };
    task: TaskData | null;
    resetWarning: boolean;
};

export default function TaskForm({ action, options, task, resetWarning }: Props) {
    const [workMode, setWorkMode] = useState(task?.workModeValue ?? '');
    const hasWorkplace = workMode === 'onsite' || workMode === 'hybrid';

    return (
        <Form {...action} className="space-y-7">
            {({ errors, processing }) => (
                <>
                    {resetWarning && <div role="alert" className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">Saving these changes will send this task back for review and clear the previous review note.</div>}
                    <section className="space-y-4 rounded-xl border p-5">
                        <h2 className="text-lg font-semibold">Task details</h2>
                        <div className="grid gap-2"><Label htmlFor="title">Task title</Label><Input id="title" name="title" maxLength={150} defaultValue={task?.title} required /><InputError message={errors.title} /></div>
                        <div className="grid gap-2"><Label htmlFor="category">Category</Label><select id="category" name="category" defaultValue={task?.category ? options.categories.find((option) => option.label === task.category)?.value ?? '' : ''} className="h-10 rounded-md border bg-background px-3 text-sm" required><option value="" disabled>Select a category</option>{options.categories.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select><InputError message={errors.category} /></div>
                        <div className="grid gap-2"><Label htmlFor="description">Task description</Label><Textarea id="description" name="description" defaultValue={task?.description} required /><InputError message={errors.description} /></div>
                        <div className="grid gap-2"><Label htmlFor="required_activities">Required activities</Label><Textarea id="required_activities" name="required_activities" defaultValue={task?.requiredActivities} required /><InputError message={errors.required_activities} /></div>
                        <div className="grid gap-2"><Label htmlFor="deliverables">Expected deliverables</Label><Textarea id="deliverables" name="deliverables" defaultValue={task?.deliverables} required /><InputError message={errors.deliverables} /></div>
                        <div className="grid gap-2"><Label htmlFor="skill_requirements">Skills or experience needed (optional)</Label><Textarea id="skill_requirements" name="skill_requirements" defaultValue={task?.skillRequirements ?? ''} /><InputError message={errors.skill_requirements} /></div>
                        <div className="grid gap-2"><Label htmlFor="estimated_duration_hours">Estimated duration in hours</Label><Input id="estimated_duration_hours" name="estimated_duration_hours" type="number" min="0.5" max="500" step="0.1" defaultValue={task?.estimatedDurationHours} required /><InputError message={errors.estimated_duration_hours} /></div>
                        <div className="grid gap-2"><Label htmlFor="complexity">Complexity</Label><select id="complexity" name="complexity" defaultValue={task?.complexity ? options.complexities.find((option) => option.label === task.complexity)?.value ?? '' : ''} className="h-10 rounded-md border bg-background px-3 text-sm" required><option value="" disabled>Select complexity</option>{options.complexities.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select><InputError message={errors.complexity} /></div>
                    </section>
                    <section className="space-y-4 rounded-xl border p-5">
                        <h2 className="text-lg font-semibold">Work details</h2>
                        <div className="grid gap-2"><Label htmlFor="work_mode">Work mode</Label><select id="work_mode" name="work_mode" value={workMode} onChange={(event) => setWorkMode(event.target.value)} className="h-10 rounded-md border bg-background px-3 text-sm" required><option value="" disabled>Select a work mode</option>{options.workModes.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select><InputError message={errors.work_mode} /></div>
                        {hasWorkplace && <><p className="text-sm text-muted-foreground">In-person work details are shown to students before they apply. Enter a place of work, not a home address.</p><div className="grid gap-2"><Label htmlFor="workplace_name">Workplace name</Label><Input id="workplace_name" name="workplace_name" defaultValue={task?.workplaceName ?? ''} required /><InputError message={errors.workplace_name} /></div><div className="grid gap-2"><Label htmlFor="workplace_address">Workplace address</Label><Input id="workplace_address" name="workplace_address" defaultValue={task?.workplaceAddress ?? ''} required /><InputError message={errors.workplace_address} /></div></>}
                        <div className="grid gap-2"><Label htmlFor="deadline">Deadline (optional)</Label><Input id="deadline" name="deadline" type="date" min={new Date(Date.now() + 86400000).toISOString().slice(0, 10)} defaultValue={task?.deadline ?? ''} /><InputError message={errors.deadline} /></div>
                    </section>
                    <section className="space-y-4 rounded-xl border p-5">
                        <h2 className="text-lg font-semibold">Compensation</h2>
                        <div className="grid gap-2"><Label htmlFor="budget">Estimated budget (PHP)</Label><Input id="budget" name="budget" type="number" min="1" max="1000000" step="0.01" defaultValue={task?.budget} required /><InputError message={errors.budget} /><p className="text-sm text-muted-foreground">TaskMate records agreements and payment status only. Payments are made directly between users. TaskMate does not hold, process, or guarantee payment.</p></div>
                    </section>
                    <section className="space-y-4 rounded-xl border p-5">
                        <h2 className="text-lg font-semibold">Rules</h2>
                        <div className="rounded-lg border bg-muted/40 p-4"><h3 className="font-medium">Task guidelines</h3><ul className="mt-2 list-disc space-y-1 pl-5 text-sm">{options.guidelines.map((guideline) => <li key={guideline}>{guideline}</li>)}</ul></div>
                        <div className="flex items-start gap-2"><input id="guidelines_confirmed" name="guidelines_confirmed" type="checkbox" value="1" required className="mt-1" /><Label htmlFor="guidelines_confirmed">I confirm that this task follows the platform rules.</Label></div><InputError message={errors.guidelines_confirmed} />
                    </section>
                    <Button type="submit" disabled={processing}>{task ? 'Save changes' : 'Submit task for review'}</Button>
                </>
            )}
        </Form>
    );
}
