<template>
  <div class="task-list">
    <template v-if="$store.state.config.addons.taskcenter">
      <Navbar />
      <HeadTab v-model="tab_active" :tabs="tabs" @tab-change="onTab" />
      <List
        class="list"
        v-model="loading"
        :finished="finished"
        :error.sync="error"
        :is-empty="isListEmpty"
        :empty="{message:'没有相关任务'}"
        @load="loadList"
      >
        <ListItem v-for="(item,index) in list" :key="index" :items="item">
          <TaskState :state="params.task_status" slot="right" />
        </ListItem>
      </List>
    </template>
    <Empty v-else page-type="fail" message="未开启任务中心应用" :show-foot="false" />
  </div>
</template>

<script>
import sfc from "@/utils/create";
import Empty from "@/components/Empty";
import HeadTab from "@/components/HeadTab";
import ListItem from "./component/ListItem";
import TaskState from "./component/TaskState";
import { GET_TASKLIST } from "@/api/task";
import { list } from "@/mixins";
export default sfc({
  name: "task-list",
  data() {
    return {
      info: {},
      tab_active: 0,
      tabs: [
        {
          name: "进行中",
          task_status: 1
        },
        {
          name: "已完成",
          task_status: 2
        },
        {
          name: "已失效",
          task_status: 3
        }
      ],
      params: {
        task_status: 1
      }
    };
  },
  mixins: [list],
  mounted() {
    this.loadList();
  },
  methods: {
    onTab(index) {
      const $this = this;
      const task_status = $this.tabs[index].task_status;
      $this.params.task_status = task_status;
      $this.loadList("init");
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_TASKLIST($this.params)
        .then(({ data }) => {
          let list = data.user_task_info ? data.user_task_info.task_info : [];
          $this.info = data.user_task_info.user_info;
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  },
  components: {
    Empty,
    HeadTab,
    ListItem,
    TaskState
  }
});
</script>

<style scoped>
</style>
