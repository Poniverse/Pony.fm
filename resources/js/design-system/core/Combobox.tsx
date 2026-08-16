import React from 'react';
import {
    Combobox as ComboboxRoot,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxList,
    ComboboxTrigger,
} from '@/design-system/primitives/combobox';

/**
 * A searchable multi-select dropdown: the trigger is whatever child you pass,
 * the panel holds the typeahead input and list. The panel stays open while
 * options are toggled and closes on outside click or Escape.
 */
export function MultiCombobox({ options, selected = [], onToggle, placeholder = 'Search…', emptyText = 'No matches.', children }: {
    options: string[];
    /** Options rendered with a check mark. */
    selected?: string[];
    /** Called with the single option that was just selected or deselected. */
    onToggle: (option: string) => void;
    placeholder?: string;
    emptyText?: string;
    /** The trigger element. */
    children: React.ReactElement;
}) {
    const change = (next: string[]) => {
        const changed = next.find((v) => !selected.includes(v))
            ?? selected.find((v) => !next.includes(v));
        if (changed !== undefined) onToggle(changed);
    };

    return (
        <ComboboxRoot items={options} multiple value={selected} onValueChange={change}>
            <ComboboxTrigger
                render={children}
                className="[&_[data-slot=combobox-trigger-icon]]:size-3 [&_[data-slot=combobox-trigger-icon]]:text-current [&_[data-slot=combobox-trigger-icon]]:opacity-80"
            />
            <ComboboxContent className="w-[230px]" anchor={undefined}>
                <ComboboxInput placeholder={placeholder} showTrigger={false} />
                <ComboboxEmpty>{emptyText}</ComboboxEmpty>
                <ComboboxList>
                    {(option: string) => (
                        <ComboboxItem key={option} value={option}>
                            {option}
                        </ComboboxItem>
                    )}
                </ComboboxList>
            </ComboboxContent>
        </ComboboxRoot>
    );
}
