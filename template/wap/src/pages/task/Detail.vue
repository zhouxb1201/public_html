<template>
  <Layout ref="load" class="task-detail">
    <Navbar />
    <DetailHead :info="detail" />
    <DetailCellGroup title="任务时间" :content="detail.start_task_time+' ~ '+detail.end_task_time" />
    <DetailCellGroup title="任务要求">
      <div class="text-regular" v-if="detail.task_kind == 2">
        每隔
        <span class="text-maintone">{{detail.task_limit_time}}</span> 小时可重新领取，领取后
        <span class="text-maintone">{{detail.task_limit_time}}</span> 小时内完成
      </div>
      <div class="text-regular" v-else>
        领取后
        <span class="text-maintone">{{detail.task_limit_time}}</span> 小时内完成
      </div>
    </DetailCellGroup>
    <DetailRuleRewardGroup
      v-for="(item,index) in detail.task_rule_reward"
      :key="index"
      :items="item"
    />
    <DetailCellGroup title="任务说明" :content="detail.task_explain" />
    <div class="fixed-foot-btn-group" v-if="showReceiveBtn">
      <van-button
        size="normal"
        type="danger"
        round
        block
        :disabled="isDisabled"
        :loading="isLoading"
        @click="onReceive"
      >{{btnText}}</van-button>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_TASKDETAIL, RECEIVE_TASK } from "@/api/task";
import DetailHead from "./component/DetailHead";
import DetailCellGroup from "./component/DetailCellGroup";
import DetailRuleRewardGroup from "./component/DetailRuleRewardGroup";
export default sfc({
  name: "task-detail",
  data() {
    return {
      isLoading: false,
      detail: {}
    };
  },
  computed: {
    showReceiveBtn() {
      return this.detail.task_kind == 3 || this.detail.task_kind == 4
        ? this.detail.is_get === 1
          ? true
          : false
        : false;
    },
    isDisabled() {
      return this.detail.is_get === 0 ? false : true;
    },
    btnText() {
      return this.isDisabled ? this.detail.end_task_time + " 失效" : "立即领取";
    }
  },
  mounted() {
    if (this.$store.state.config.addons.taskcenter) {
      this.loadData();
    } else {
      this.$refs.load.fail({
        errorText: "未开启任务中心应用",
        showFoot: false
      });
    }
  },
  methods: {
    loadData() {
      GET_TASKDETAIL(this.$route.params.id, this.$route.query.user_task_id)
        .then(({ data }) => {
          this.detail = data.general_task_detail;
          this.$refs.load.success();
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    },
    onReceive() {
      const id = this.$route.params.id;
      this.isLoading = true;
      RECEIVE_TASK(id)
        .then(({ message }) => {
          this.$Toast.success(message);
          this.isLoading = false;
          this.loadData();
        })
        .catch(() => {
          this.isLoading = false;
        });
    }
  },
  components: {
    DetailHead,
    DetailCellGroup,
    DetailRuleRewardGroup
  }
});
</script>

<style scoped>
.task-detail {
  background: #fff;
  margin-bottom: 74px;
}
</style>
