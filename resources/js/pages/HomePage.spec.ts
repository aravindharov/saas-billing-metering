import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import HomePage from './HomePage.vue';

describe('HomePage', () => {
    it('renders the dashboard heading', () => {
        const wrapper = mount(HomePage);
        expect(wrapper.text()).toContain('Dashboard');
    });

    it('displays the planned-phases notice', () => {
        const wrapper = mount(HomePage);
        expect(wrapper.text()).toContain('Business modules will be implemented');
    });
});
