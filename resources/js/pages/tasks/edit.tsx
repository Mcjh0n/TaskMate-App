import { Head } from '@inertiajs/react';
import TaskForm from '@/components/task-form';
import { update } from '@/routes/tasks';

type Option = { value: string; label: string };
type TaskData = {
    id: number; title: string; category: string; description: string; requiredActivities: string; deliverables: string;
    estimatedDurationHours: string; skillRequirements: string | null; complexity: string; budget: string;
    workModeValue: string; workplaceName: string | null; workplaceAddress: string | null; deadline: string | null;
};
type Props = {
    options: { categories: Option[]; workModes: Option[]; complexities: Option[]; guidelines: string[] };
    task: TaskData;
    resetWarning: boolean;
};

export default function EditTask({ options, task, resetWarning }: Props) {
    return <><Head title="Edit task" /><div className="mx-auto max-w-4xl space-y-5 p-4 sm:p-6"><div><h1 className="text-2xl font-semibold">Edit task</h1><p className="mt-1 text-sm text-muted-foreground">Update the work details and submit them for review.</p></div><TaskForm action={update.form(task.id)} options={options} task={task} resetWarning={resetWarning} /></div></>;
}
