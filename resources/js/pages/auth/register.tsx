import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { SafetyNotice } from '@/components/safety-notice';
import TeamInvitationAlert from '@/components/team-invitation-alert';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { login } from '@/routes';
import { store } from '@/routes/register';
import type { TeamInvitationContext } from '@/types';

type UserType = 'student' | 'faculty_staff' | 'organization';

type Props = {
    passwordRules: string;
    institutionalEmailDomains: string[];
    teamInvitation?: TeamInvitationContext | null;
};

export default function Register({ passwordRules, institutionalEmailDomains, teamInvitation }: Props) {
    const [userType, setUserType] = useState<UserType>('student');
    const affiliationLabel = userType === 'student' ? 'College and program' : 'College or office';
    const idLabel = userType === 'student' ? 'Student ID number' : 'Employee ID number';

    return (
        <>
            <Head title="Register" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        {teamInvitation && <TeamInvitationAlert invitation={teamInvitation} action="Register" />}
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="user_type">Choose your account type</Label>
                                <select
                                    id="user_type"
                                    name="user_type"
                                    value={userType}
                                    onChange={(event) => setUserType(event.target.value as UserType)}
                                    required
                                    className="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                >
                                    <option value="student">CMU Student</option>
                                    <option value="faculty_staff">CMU Faculty / Staff</option>
                                    <option value="organization">Office or Organization Representative</option>
                                </select>
                                <InputError message={errors.user_type} />
                            </div>

                            <SafetyNotice />

                            {userType === 'organization' ? (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="organization_name">Organization name</Label>
                                        <Input id="organization_name" name="organization_name" required placeholder="Organization name" />
                                        <InputError message={errors.organization_name} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="position">Your position</Label>
                                        <Input id="position" name="position" required placeholder="Representative position" />
                                        <InputError message={errors.position} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="supporting_info">About the organization and why it posts tasks</Label>
                                        <Textarea id="supporting_info" name="supporting_info" required maxLength={1000} placeholder="Briefly describe your organization and why you want to post tasks." />
                                        <InputError message={errors.supporting_info} />
                                    </div>
                                </>
                            ) : (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="id_number">{idLabel}</Label>
                                        <Input id="id_number" name="id_number" required placeholder={idLabel} />
                                        <InputError message={errors.id_number} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="affiliation">{affiliationLabel}</Label>
                                        <Input id="affiliation" name="affiliation" required placeholder={affiliationLabel} />
                                        <InputError message={errors.affiliation} />
                                    </div>
                                </>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="name">{userType === 'organization' ? 'Representative name' : 'Name'}</Label>
                                <Input id="name" name="name" required autoFocus autoComplete="name" placeholder="Full name" />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input id="email" type="email" required autoComplete="email" name="email" placeholder="email@example.com" />
                                <InputError message={errors.email} />
                                {userType !== 'organization' && (
                                    <p className="text-sm text-muted-foreground">Use your CMU institutional email address ({institutionalEmailDomains.map((domain) => `@${domain}`).join(', ')}).</p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput id="password" required autoComplete="new-password" name="password" placeholder="Password" passwordrules={passwordRules} />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">Confirm password</Label>
                                <PasswordInput id="password_confirmation" required autoComplete="new-password" name="password_confirmation" placeholder="Confirm password" passwordrules={passwordRules} />
                                <InputError message={errors.password_confirmation} />
                            </div>

                            <Button type="submit" className="mt-2 w-full" disabled={processing} data-test="register-user-button">
                                {processing && <Spinner />}
                                Create account
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink href={teamInvitation ? login.url({ query: { invitation: teamInvitation.code } }) : login()} data-test="team-invitation-login-link">
                                Log in
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Create an account',
    description: 'Enter your details below to create your account',
};
