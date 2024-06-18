import {createIslandWebComponent} from 'preact-island'
import {useEffect, useRef, useState} from "preact/compat";
import {DrupalJsonApiParams} from "drupal-jsonapi-params";
import styled from "styled-components";
import Jsona from "jsona";
import Moment from "moment";
import useOutsideClick from "./useOutsideClick";
import {useBoolean} from "usehooks-ts";

const islandName = 'event-calendar-island'

const MonthNav = styled.nav`
  display: flex;
  justify-content: space-between;
`
const Button = styled.button`
  background: #f4f4f4;
  color: #b1040e;
  cursor: pointer;
  padding: 12px 5px;
  transition: background-color .25s ease-in-out, color .25s ease-in-out;

  &:hover, &:focus {
    background: #2e2d29;
    color: #fff;
  }
`

const TileButton = styled(Button)`
  width: 100%;
`

const List = styled.ul`
  list-style: none;
  margin: 0;
  padding: 0;

  li {
    margin: 10px 0;
    padding: 10px 0;
    border-bottom: 1px solid black;

    &:last-child {
      border-bottom: none;
    }
  }

  a {
    color: #b1040e;
    text-decoration: none;
    font-size: 1.3em;

    &:hover, &:focus {
      color: #2e2d29;
      text-decoration: underline;
    }
  }
`

const Table = styled.table`
  width: 100%;
  border-spacing: 10px;
  border-collapse: separate;

  td, th {
    padding: 0;
    text-align: center;
    width: 14.2%;
  }
`

type EventNode = {
  id: string,
  title: string,
  path: { alias: string },
}

const EventCalendar = () => {
  const apiUrl = (process.env?.LOCAL_DRUPAL ?? '') + '/jsonapi/node/stanford_event';
  const nextButtonRef = useRef<HTMLButtonElement>(null);

  const [events, setEvents] = useState<EventNode[]>([])
  const [shownDate, setCurrentMonth] = useState<Date>(new Date())
  const currentMonth = shownDate;
  currentMonth.setDate(1);
  currentMonth.setHours(0);
  currentMonth.setMinutes(0);
  currentMonth.setSeconds(0);

  const fetchEvents = async () => {
    const dataFormatter = new Jsona()
    const url = new URL(apiUrl.startsWith('/') ? window.location.origin + apiUrl : apiUrl);
    const params = new DrupalJsonApiParams();
    params.addFilter('status', '1');
    params.addFilter('su_event_date_time.value', Math.floor(currentMonth.getTime() / 1000).toString(), '>=')
    params.addFilter('su_event_date_time.value', Math.floor(new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1).getTime() / 1000).toString(), '<')
    params.addPageLimit(50);

    params.addSort('su_event_date_time.value', 'ASC')
    url.search = params.getQueryString();

    let events = [];
    let fetchEvents: EventNode[] = [];
    let fetchMore = true;
    let page = 1;

    while (fetchMore) {
      url.search = params.getQueryString();

      fetchEvents = await fetch(url.toString(), {cache: "force-cache"})
        .then(response => response.json())
        .then(data => dataFormatter.deserialize(data) as EventNode[])

      params.addPageOffset(50 * page);
      fetchMore = fetchEvents.length >= 50;

      events = [...events, ...fetchEvents];
    }
    setEvents(events);
  }

  useEffect(() => {
    fetchEvents()
  }, [currentMonth])

  let weeks = [];
  let days = [];

  const daysInMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 0).getDate();

  // Loop through every date of the current displayed month.
  for (let i = 1; i <= daysInMonth; i++) {
    // If we are on the first of the month, pad the week with any weekdays if the first is not on a Sunday.
    if (i === 1) {
      const dayOfWeek = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), i).getDay()
      while (days.length < dayOfWeek) days.push(0);
    }

    days.push(i);

    if (i === daysInMonth && days.length < 7) {
      while (days.length < 7) days.push(0);
    }

    if (days.length === 7) {
      weeks.push(days);
      days = [];
    }
  }

  const previousMonth = () => {
    setCurrentMonth(new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1))
  }
  const nextMonth = () => {
    setCurrentMonth(new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1))
  }

  return (
    <div>
      <MonthNav aria-label="Change calendar month">
        <Button onClick={previousMonth}>
          <i className="fas fa-chevron-left"/>
          <span className="visually-hidden">Previous Month</span>
        </Button>

        {Moment(currentMonth).format('MMMM YY') != Moment(new Date()).format('MMMM YY') &&
          <Button onClick={() => {
            setCurrentMonth(new Date());
            nextButtonRef.current.focus()
          }}>
            <span className="visually-hidden">Return to&nbsp;</span>Today
          </Button>
        }

        <Button onClick={nextMonth} ref={nextButtonRef}>
          <i className="fas fa-chevron-right"/>
          <span className="visually-hidden">Next Month</span>
        </Button>
      </MonthNav>

      <Table>
        <caption
          aria-live="polite"
          aria-atomic
          aria-current={Moment(currentMonth).format('MMMM YYYY') === Moment().format('MMMM YYYY') ? 'date' : undefined}
        >
          {Moment(currentMonth).format('MMMM YYYY')}
        </caption>
        <thead>
        <tr>
          <th scope="col" abbr="Sunday">Sun</th>
          <th scope="col" abbr="Monday">Mon</th>
          <th scope="col" abbr="Tuesday">Tue</th>
          <th scope="col" abbr="Wednesday">Wed</th>
          <th scope="col" abbr="Thursday">Thu</th>
          <th scope="col" abbr="Friday">Fri</th>
          <th scope="col" abbr="Saturday">Sat</th>
        </tr>
        </thead>

        <tbody>
        {weeks.map(week => (
          <tr>
            {week.map(day => (
              <td>
                {day > 0 &&
                  <DayTile
                    date={new Date(currentMonth.getFullYear(), currentMonth.getMonth(), day)}
                    events={events}
                  />
                }
              </td>
            ))}
          </tr>
        ))}
        </tbody>
      </Table>
    </div>
  )
}

const Dialog = styled.dialog`
  border: none;
  border-radius: 4px;
  box-shadow: rgba(0, 0, 0, 0.2) 0px 5px 5px -3px, rgba(0, 0, 0, 0.14) 0px 8px 10px 1px, rgba(0, 0, 0, 0.12) 0px 3px 14px 2px;
  z-index: 10;
  text-align: left;
  max-height: 0;
  overflow: hidden;
  transition: max-height .3s;

  &.open {
    max-height: 100%;
    overflow-y: scroll;
  }
`

const CloseButton = styled.button`
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 0;
  background: none;
  z-index: 10;
  color: #b1040e;
  cursor: pointer;

  &:hover, &:focus {
    color: #2e2d29;
    background: none;
    box-shadow: none;
  }
`

const DayTile = ({date, events}) => {
  const {value: dialogOpen, setFalse: setDialogClosed, toggle: toggleDialog} = useBoolean()

  const dayEvents = events.filter(event => {
    const start = new Date(event.su_event_date_time.value)
    return start.toLocaleDateString() === date.toLocaleDateString()
  })
  const dialogRef = useRef<HTMLDialogElement>(null);
  const buttonRef = useRef<HTMLButtonElement>(null);

  const closeDialog = () => {
    if (dialogOpen) {
      setDialogClosed()
      buttonRef.current.focus();
    }
  }
  useOutsideClick(dialogRef, closeDialog)

  if (dayEvents.length) {
    return (
      <>
        <TileButton
          ref={buttonRef}
          onClick={toggleDialog}
          aria-label={Moment(date).format('MMM Do YYYY')}
          aria-current={date.toLocaleDateString() === new Date().toLocaleDateString() ? 'date' : undefined}
        >
          {date.getDate()}
        </TileButton>

        {dialogOpen &&
          <Dialog
            open={true}
            className="open" ref={dialogRef}
            aria-label={Moment(date).format('MMM Do, YYYY') + ' Events'}
          >
            <CloseButton onClick={closeDialog}>
              <i className="far fa-window-close"/>
              <span className="visually-hidden">Close Dialog</span>
            </CloseButton>
            <List>
              {dayEvents.map(event =>
                <li key={event.id}>
                  <EventTile event={event}/>
                </li>
              )}
            </List>
          </Dialog>
        }
      </>
    )
  }
  return (
    <abbr title={Moment(date).format('MMM Do YYYY')}>{date.getDate()}</abbr>
  )
}

const EventTile = ({event}) => {
  const startTime = Moment(event.su_event_date_time.value).format('h:mm A');
  const endTime = Moment(event.su_event_date_time.end_value).format('h:mm A');
  // Smart date stores the values that are all day as midnight and 11:59 PM, so
  // check for those values and provide a string with the duration.
  const duration = startTime === '12:00 AM' && endTime === '11:59 PM' ? 'All Day' : `${startTime} to ${endTime}`

  return (
    <>
      <div><a href={event.path.alias}>{event.title}</a></div>
      <div>{duration}</div>
    </>
  )
}

const island = createIslandWebComponent(islandName, EventCalendar)
island.render({
  selector: `event-mini-cal, [data-island="${islandName}"]`,
})
