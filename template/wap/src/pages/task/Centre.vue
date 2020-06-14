<template>
  <div class="task-centre">
    <template v-if="$store.state.config.addons.taskcenter">
      <Navbar />
      <Header :info="info" />
      <van-tabs v-model="active" @change="onTab">
        <van-tab :title="item.name" v-for="(item,t) in tabs" :key="t" />
      </van-tabs>
      <List
        class="list"
        v-model="loading"
        :finished="finished"
        :error.sync="error"
        :is-empty="isListEmpty"
        :empty="{message:'没有相关任务',top:$store.state.isWeixin?126:166}"
        @load="loadList"
      >
        <ListItem v-for="(item,index) in list" :key="index" :items="item">
          <ReceiveBtn :state="item.task_info_status" :id="item.general_poster_id" slot="right" />
        </ListItem>
      </List>
    </template>
    <Empty v-else page-type="fail" message="未开启任务中心应用" :show-foot="false" />
  </div>
</template>

<script>
import sfc from "@/utils/create";
import Empty from "@/components/Empty";
import Header from "./component/Header";
import ListItem from "./component/ListItem";
import ReceiveBtn from "./component/ReceiveBtn";
import { GET_TASKCENTRE } from "@/api/task";
import { list } from "@/mixins";
export default sfc({
  name: "task-centre",
  data() {
    return {
      info: {},
      active: 0,
      tabs: [
        {
          name: "单次任务",
          task_kind: 1
        },
        {
          name: "多次任务",
          task_kind: 2
        }
      ],
      params: {
        task_kind: 1
      }
    };
  },
  mixins: [list],
  mounted() {
    if (this.$store.state.config.addons.taskcenter) this.loadList();
  },
  methods: {
    onTab(index) {
      const $this = this;
      const task_kind = $this.tabs[index].task_kind;
      $this.params.task_kind = task_kind;
      $this.loadList("init");
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_TASKCENTRE($this.params)
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
    Header,
    ListItem,
    ReceiveBtn
  }
});
</script>

<style scoped>
.task-centre {
  background: #fff;
}
</style>
