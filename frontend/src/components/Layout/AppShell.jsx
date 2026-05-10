import React from 'react';
import PropTypes from 'prop-types';
import Sidebar from './Sidebar';
import Header from './Header';
import { Notification } from '../Shared/Notification';

function AppShell({ children }) {
  return (
    <div className="bogo-flex bogo-min-h-screen">
      <Sidebar />
      <div className="bogo-flex-1 bogo-flex bogo-flex-col">
        <Header />
        <main className="bogo-flex-1 bogo-p-6 bogo-bg-gray-50">
          {children}
        </main>
      </div>
      <Notification />
    </div>
  );
}

AppShell.propTypes = {
  children: PropTypes.node.isRequired,
};

export default AppShell;
