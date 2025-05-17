import React, { useState, useEffect } from 'react';
import axios from 'axios';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js';
import { Line, Doughnut } from 'react-chartjs-2';
import styled from 'styled-components';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
);

const Container = styled.div`
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
`;

const Header = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
`;

const DateFilter = styled.div`
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  display: flex;
  gap: 1rem;
  align-items: center;
`;

const StatsGrid = styled.div`
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
`;

const StatCard = styled.div`
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  text-align: center;
`;

const StatNumber = styled.div`
  font-size: 2.5rem;
  font-weight: bold;
  color: #0d6efd;
  margin-bottom: 0.5rem;
`;

const StatLabel = styled.div`
  color: #6c757d;
  font-size: 1rem;
`;

const ChartContainer = styled.div`
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  margin-bottom: 2rem;
`;

const Table = styled.table`
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
  
  th, td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
  }
  
  th {
    background: #f8f9fa;
    font-weight: 600;
  }
`;

const ExportButton = styled.button`
  background: #28a745;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  
  &:hover {
    background: #218838;
  }
`;

const Analytics = () => {
  const [dateFrom, setDateFrom] = useState(new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]);
  const [dateTo, setDateTo] = useState(new Date().toISOString().split('T')[0]);
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchData = async () => {
    try {
      setLoading(true);
      const response = await axios.get('/api/admin/analytics', {
        params: { dateFrom, dateTo }
      });
      setData(response.data);
      setError(null);
    } catch (err) {
      setError('Error fetching analytics data');
      console.error('Analytics error:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, [dateFrom, dateTo]);

  const handleExport = () => {
    window.location.href = `/api/admin/analytics/export?dateFrom=${dateFrom}&dateTo=${dateTo}`;
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;
  if (!data) return null;

  const { stats, statusStats, agencyStats, dailyStats } = data;

  const ticketActivityData = {
    labels: dailyStats.map(stat => stat.date),
    datasets: [
      {
        label: 'New Tickets',
        data: dailyStats.map(stat => stat.newTickets),
        borderColor: '#0d6efd',
        tension: 0.1
      },
      {
        label: 'Resolved Tickets',
        data: dailyStats.map(stat => stat.resolvedTickets),
        borderColor: '#28a745',
        tension: 0.1
      }
    ]
  };

  const statusDistributionData = {
    labels: statusStats.map(stat => stat.status),
    datasets: [{
      data: statusStats.map(stat => stat.count),
      backgroundColor: [
        '#ffc107', // pending
        '#0dcaf0', // in_progress
        '#6c757d', // under_review
        '#28a745', // resolved
        '#dc3545'  // closed
      ]
    }]
  };

  return (
    <Container>
      <Header>
        <h1>Ticket Analytics</h1>
        <DateFilter>
          <label>
            From:
            <input
              type="date"
              value={dateFrom}
              onChange={e => setDateFrom(e.target.value)}
            />
          </label>
          <label>
            To:
            <input
              type="date"
              value={dateTo}
              onChange={e => setDateTo(e.target.value)}
            />
          </label>
          <ExportButton onClick={handleExport}>
            Export Report
          </ExportButton>
        </DateFilter>
      </Header>

      <StatsGrid>
        <StatCard>
          <StatNumber>{stats.totalTickets}</StatNumber>
          <StatLabel>Total Tickets</StatLabel>
        </StatCard>
        <StatCard>
          <StatNumber>{stats.resolvedTickets}</StatNumber>
          <StatLabel>Resolved Tickets</StatLabel>
        </StatCard>
        <StatCard>
          <StatNumber>{(stats.avgResolutionTime / 24).toFixed(1)}</StatNumber>
          <StatLabel>Avg. Resolution Time (Days)</StatLabel>
        </StatCard>
        <StatCard>
          <StatNumber>{stats.highPriorityTickets}</StatNumber>
          <StatLabel>High Priority Tickets</StatLabel>
        </StatCard>
      </StatsGrid>

      <ChartContainer>
        <h2>Ticket Activity Over Time</h2>
        <Line
          data={ticketActivityData}
          options={{
            responsive: true,
            scales: {
              y: {
                beginAtZero: true
              }
            }
          }}
        />
      </ChartContainer>

      <ChartContainer>
        <h2>Ticket Status Distribution</h2>
        <Doughnut
          data={statusDistributionData}
          options={{
            responsive: true,
            plugins: {
              legend: {
                position: 'right'
              }
            }
          }}
        />
      </ChartContainer>

      <ChartContainer>
        <h2>Agency Performance</h2>
        <Table>
          <thead>
            <tr>
              <th>Agency</th>
              <th>Total Tickets</th>
              <th>Avg. Resolution Time (Days)</th>
              <th>Resolution Rate</th>
            </tr>
          </thead>
          <tbody>
            {agencyStats.map(agency => (
              <tr key={agency.name}>
                <td>{agency.name}</td>
                <td>{agency.ticketCount}</td>
                <td>{(agency.avgResolutionTime / 24).toFixed(1)}</td>
                <td>{agency.resolutionRate.toFixed(1)}%</td>
              </tr>
            ))}
          </tbody>
        </Table>
      </ChartContainer>
    </Container>
  );
};

export default Analytics; 