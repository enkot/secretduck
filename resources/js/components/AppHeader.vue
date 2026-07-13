<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { MailOpen, Menu } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import TeamSwitcher from '@/components/TeamSwitcher.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { index as invitationsIndex } from '@/routes/invitations';
import type { BreadcrumbItem, NavItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);
const { isCurrentOrParentUrl } = useCurrentUrl();

const invitationsUrl = computed(() =>
    page.props.currentTeam
        ? invitationsIndex(page.props.currentTeam.slug).url
        : '/',
);

const activeItemStyles = 'bg-accent text-accent-foreground';

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Invitations',
        href: invitationsUrl.value,
        icon: MailOpen,
    },
]);
</script>

<template>
    <header
        data-test="app-top-header"
        class="sticky top-0 z-40 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80"
    >
        <div
            class="mx-auto flex h-16 w-full max-w-7xl items-center gap-2 px-4 sm:px-6 lg:px-8"
        >
            <!-- Mobile Menu -->
            <div class="lg:hidden">
                <Sheet>
                    <SheetTrigger :as-child="true">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-10"
                            aria-label="Open navigation menu"
                        >
                            <Menu class="h-5 w-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent
                        side="left"
                        class="w-[min(20rem,85vw)] gap-0 p-0"
                    >
                        <SheetTitle class="sr-only">Navigation menu</SheetTitle>
                        <div class="border-b p-6">
                            <Link
                                :href="invitationsUrl"
                                class="flex items-center"
                            >
                                <AppLogo />
                            </Link>
                        </div>
                        <div class="border-b p-4 sm:hidden">
                            <TeamSwitcher :in-header="true" />
                        </div>
                        <div class="flex flex-1 flex-col p-4">
                            <nav class="grid gap-1">
                                <Link
                                    v-for="item in mainNavItems"
                                    :key="item.title"
                                    :href="item.href"
                                    class="flex min-h-11 items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
                                    :class="
                                        isCurrentOrParentUrl(item.href)
                                            ? activeItemStyles
                                            : null
                                    "
                                    :aria-current="
                                        isCurrentOrParentUrl(item.href)
                                            ? 'page'
                                            : undefined
                                    "
                                >
                                    <component
                                        v-if="item.icon"
                                        :is="item.icon"
                                        class="h-5 w-5"
                                    />
                                    {{ item.title }}
                                </Link>
                            </nav>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>

            <Link
                :href="invitationsUrl"
                class="flex shrink-0 items-center [&>div:last-child]:hidden sm:[&>div:last-child]:grid"
            >
                <AppLogo />
            </Link>

            <!-- Desktop Menu -->
            <div class="hidden h-full lg:flex lg:flex-1">
                <NavigationMenu class="ml-10 flex h-full items-stretch">
                    <NavigationMenuList
                        class="flex h-full items-stretch space-x-2"
                    >
                        <NavigationMenuItem
                            v-for="(item, index) in mainNavItems"
                            :key="index"
                            class="relative flex h-full items-center"
                        >
                            <Link
                                :class="[
                                    navigationMenuTriggerStyle(),
                                    isCurrentOrParentUrl(item.href)
                                        ? activeItemStyles
                                        : null,
                                    'h-9 cursor-pointer px-3',
                                ]"
                                :href="item.href"
                                :aria-current="
                                    isCurrentOrParentUrl(item.href)
                                        ? 'page'
                                        : undefined
                                "
                            >
                                <component
                                    v-if="item.icon"
                                    :is="item.icon"
                                    class="mr-2 h-4 w-4"
                                />
                                {{ item.title }}
                            </Link>
                            <div
                                v-if="isCurrentOrParentUrl(item.href)"
                                class="absolute inset-x-2 bottom-0 h-0.5 translate-y-px rounded-full bg-primary"
                            ></div>
                        </NavigationMenuItem>
                    </NavigationMenuList>
                </NavigationMenu>
            </div>

            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                <div class="hidden sm:block">
                    <TeamSwitcher :in-header="true" />
                </div>

                <DropdownMenu>
                    <DropdownMenuTrigger :as-child="true">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                        >
                            <Avatar class="size-8 overflow-hidden rounded-full">
                                <AvatarImage
                                    v-if="auth.user.avatar"
                                    :src="auth.user.avatar"
                                    :alt="auth.user.name"
                                />
                                <AvatarFallback
                                    class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                >
                                    {{ getInitials(auth.user?.name) }}
                                </AvatarFallback>
                            </Avatar>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent :user="auth.user" />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>

        <div v-if="props.breadcrumbs.length > 1" class="flex w-full border-t">
            <div
                class="mx-auto flex h-11 w-full max-w-7xl items-center justify-start px-4 text-muted-foreground sm:px-6 lg:px-8"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </header>
</template>
