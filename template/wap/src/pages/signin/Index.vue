<template>
  <Layout ref="load" class="signin">
    <Navbar />
    <Header :info="info" @success="getSigninList" />
    <InlineCalendar
      ref="calendar"
      disable-select
      :renderFunction="buildSlotFn"
      @on-view-change="onChange"
    ></InlineCalendar>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import Header from "./component/Header";
import { GET_SIGNININFO, GET_SIGNINLIST } from "@/api/signin";
import { formatDate } from "@/utils/util";
import InlineCalendar from "./component/inline-calendar";
export default sfc({
  name: "signin",
  data() {
    return {
      info: {},
      signinList: []
    };
  },
  mounted() {
    if (this.$store.state.config.addons.signin) {
      this.loadData();
    } else {
      this.$refs.load.fail({ errorText: "未开启签到应用", showFoot: false });
    }
  },
  methods: {
    loadData() {
      GET_SIGNININFO().then(({ data }) => {
        this.info = data;
        this.$refs.load.success();
      });
    },
    buildSlotFn(line, index, data) {
      return this.signinList.indexOf(data.formatedDate) != -1
        ? `<small class="calendar-small"><i class="van-icon van-icon-v-icon-success3"/>已签</small>`
        : "";
    },
    onChange({ firstCurrentMonthDate }) {
      this.getSigninList(firstCurrentMonthDate);
    },
    getSigninList(time) {
      GET_SIGNINLIST({ time }).then(({ data }) => {
        data.forEach(({ sign_in_time }) => {
          this.signinList.push(formatDate(sign_in_time));
        });
      });
    }
  },
  components: {
    Header,
    InlineCalendar
  }
});
</script>

<style scoped>
</style>


